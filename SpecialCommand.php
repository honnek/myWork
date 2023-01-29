<?php

    /**
     * Создаёт файл csv с данными для скоринга по файлу xlsx со столбцами clientId и dateOfReport
     *
     * @param ClientRepository $clientRepository
     * @param LoanRepositoryInterface $loanRepository
     * @param KernelInfo $kernelInfo
     * @return void
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function actionCreateBatchRequestBankruptPredictionFile(
        ClientRepository        $clientRepository,
        LoanRepositoryInterface $loanRepository,
        KernelInfo              $kernelInfo,
    ) {
        $date = (new DateTimeImmutable())->format("Ymd");
        $filename = "3OM_SC_1_{$date}_0002.csv";
        $dir = $kernelInfo->getProjectPath() . '/common/docs_templates/temp';

        $scoringFile = fopen("$dir/$filename", 'w');

        $bankruptsList = IOFactory::load("$dir/BankruptsList_Loan_info.xlsx");

        $bankruptsList->setActiveSheetIndex(0);
        $sheet = $bankruptsList->getActiveSheet();
        $num = 0;
        $succeed = 0;

        fputcsv($scoringFile, [
            'num',
            'lastname',
            'firstname',
            'middlename',
            'birthday',
            'birthplace',
            'doctype',
            'docno',
            'docdate',
            'docplace',
            'pfno',
            'addr_reg_index',
            'addr_reg_total',
            'addr_fact_index',
            'addr_fact_total',
            'gender',
            'cred_type',
            'cred_currency',
            'cred_sum',
            'dateofreport',
            'reason',
            'reason_text',
            'consent',
            'consentdate',
            'consentenddate',
            'admcode_inform',
            'consent_owner',
            'private_inn',
            'phone_mobile',
            'phone_home',
            'phone_work',
        ], separator: ';');

        foreach ($sheet->getRowIterator(2) as $row) {
            $num++;
            $cellIterator = $row->getCellIterator();
            $clientId = intval($cellIterator->current()->getValue());
            $cellIterator->next();
            $dateOfReport = DateTimeImmutable::createFromFormat('m/d/Y', $cellIterator->current()->getFormattedValue());

            $client = $clientRepository->findByPk($clientId);
            $loan = $loanRepository->findLastActiveByClientIdAndDate($clientId, $dateOfReport);
            $app = $loan?->getApp();
            if (null === $app) {
                echo "{$num}) Для килента $clientId не найдено актуальной заявки на дату {$dateOfReport->format(DateFmt::D_DB)}\n";
                continue;
            }

            $addrRegIndex = $client->getPassportIAddress()->getPostIndex();
            $addrFactIndex = $client->getHomeIAddress()->getPostIndex();
            if (empty($addrRegIndex)) {
                $addrRegIndex = '000000';
            }

            if (empty($addrFactIndex)) {
                $addrFactIndex = '000000';
            }

            $confirmDate = $app->getApprovementDate() ?? $app->getDeclineDate();
            if (null !== $confirmDate) {
                $confirmDate = new DateTimeImmutable($confirmDate->format(DateFmt::DT_DB));
            }

            $smsConfirm = SmsConfirm::getLastSmsConfirmByClient($client, SmsConfirm::TYPE_BASE, $confirmDate);
            if (null === $smsConfirm || null === $smsConfirm->getConfirmDate()) {
                $smsConfirm = $app->getSmsConfirm();
            }

            $consent = 1;
            $consentDate = null;
            $consentEndDate = null;
            if (null !== $smsConfirm) {
                $consentDate = $smsConfirm->getConfirmDate();
            } elseif (null !== $app->bkiRating) {
                $consentDate = $app->bkiRating->getCreatedAt();
            }

            if (null === $consentDate) {
                echo "{$num}) Для клиента $clientId не найдена дата соглашения\n";
                continue;
            }

            $consentDate = new DateTimeImmutable($consentDate->format(DateFmt::DT_DB));
            if (!$loan->getStatus()->isActive() && $consentDate->modify('+6month') < $dateOfReport) {
                echo "{$num}) Для клиента $clientId истекло время соглашения\n";
                continue;
            }

            $consentDate = match ($dateOfReport < $consentDate) {
                true => $dateOfReport,
                false => $consentDate,
            };
            $consentEndDate = match ($loan->getStatus()->isActive()) {
                true => $dateOfReport->modify('+6month'),
                false => $consentDate->modify('+6month'),
            };

            $firstName = $client->getFirstName();
            $lastName = $client->getLastName();
            $middleName = $client->getMiddleName();
            $birthDate = $client->getBirthDate();
            $birthPlace = $client->getBirthPlace();
            $docNo = $client->getPassportSeries() . $client->getPassportNumber();
            $docDate = $client->getPassportDate();
            $docPlace = $client->getIssuingAuthority();
            if (empty($firstName) || empty($lastName) || null === $birthDate ||
                empty($birthPlace) || empty($docNo) || null === $docDate || empty($docPlace)) {
                continue;
            }

            fwrite($scoringFile, mb_convert_encoding(implode(';', [
                    $clientId, // num
                    $lastName, // lastname
                    $firstName, // firstname
                    $middleName, //middlename
                    $birthDate->format(DateFmt::D_APP_NEW), // birthday
                    $birthPlace, // birthplace
                    1, // doctype
                    $docNo, // docno
                    $docDate->format(DateFmt::D_APP_NEW), //docdate
                    $docPlace, // docplace
                    $client->getSnils(), // pfno
                    $addrRegIndex, // addr_reg_index
                    $client->getPassportIAddress()->getFull(), // addr_reg_total
                    $addrFactIndex, // addr_fact_index
                    $client->getHomeIAddress()->getFull(), // addr_fact_total
                    $client->getSex() + 1, // gender
                    19, // cred_type
                    'RUR', // cred_currency
                    $app->getRequestedSum(), // cred_sum
                    $app->getCreationDate()->format(DateFmt::D_APP_NEW), // dateofreport
                    1, // reason
                    '', // reason_text
                    $consent, // consent
                    $consentDate->format(DateFmt::D_APP_NEW), // consentdate
                    $consentEndDate->format(DateFmt::D_APP_NEW), // consentenddate
                    1, // admcode_inform
                    '', // consent_owner
                    $client->getInn(), // private_inn
                    $client->getLoginPhone()->getDigits(), // phone_mobile
                    '', // phone_home
                    '', // phone_work
                ]) . "\n", 'WINDOWS-1251'));
            $succeed++;
            echo "{$num}) Клиент $clientId успешно добавлен в файл для скоринга \n";
        }

        fclose($scoringFile);
        echo "-------------------------------------------------------------- \n";
        echo ">>> Всего $succeed из $num добавлены в файл <<< \n\n";
    }

    /**
     * Выгружает сведения о размере задолженности по договорам по которым оформлены кредитные каникулы за промежуток времени в xls
     *
     * ./yiic Special exportCreditHolidaysReport --dateBegin='2022-03-01' --dateEnd='2022-06-30'
     *
     * @param string $dateBegin
     * @param string $dateEnd
     * @param PDO $pdo
     * @param LoggerInterface $logger
     * @param ChargeRepositoryInterface $chargeRepository
     * @return void
     * @throws BaseException
     * @throws CantSaveFileException
     */
    public function actionExportCreditHolidaysReport(
        string                    $dateBegin,
        string                    $dateEnd,
        PDO                       $pdo,
        LoggerInterface           $logger,
        ChargeRepositoryInterface $chargeRepository,
    ): void {
        $dateTimeBegin = DateFmt::dateFromDB($dateBegin);
        $dateTimeEnd = DateFmt::dateFromDB($dateEnd);

        $sql = <<<SQL
        SELECT `ch`.`loan_id`, `ch`.`date_start`, `lh`.`sum` FROM `credit_holidays` AS `ch` INNER JOIN `loan_history` AS `lh` ON 
            `ch`.`loan_id` = `lh`.`loan_id` WHERE DATE(`ch`.`date_start`) BETWEEN :start AND :end AND `ch`.`date_start` = `lh`.`active_begin`;
        SQL;

        $params = [
            ':start' => $dateBegin,
            ':end' => $dateEnd,
        ];

        $query = $pdo->prepare($sql);
        $query->execute($params);
        $loans = $query->fetchAll(PDO::FETCH_CLASS);

        foreach ($loans as $loan) {
            $date = DateFmt::fromDB($loan->date_start);
            $percent = $chargeRepository->getSumPercentTotalUpTo($loan->loan_id, $date);

            $counter = 1;
            $interval = new DateIntervalForm();
            $interval->setBegin($dateTimeBegin);
            $interval->setEnd($dateTimeEnd);

            $data [] = [
                $loan->loan_id,
                $loan->sum,
                $percent,
                $loan->sum + $percent,
            ];
            (new XlsCreditHolidaysReportExport($data, $interval, $counter, $logger))->save();
        }
    }

    /**
     * Заполняет поле reg_mode в таблице Account
     *
     * @param IClientRepository $clientRepository
     * @param CreditApplicationRepositoryInterface $creditApplicationRepository
     * @param TransactionInterface $transaction
     * @param AccountRepositoryInterface $accountRepository
     * @param EsiaRequestPersonDataRepositoryInterface $esiaRequestPersonDataRepository
     * @param PDO $pdo
     * @return void
     */
    public function actionFillAccountTableRegMode(
        IClientRepository                        $clientRepository,
        CreditApplicationRepositoryInterface     $creditApplicationRepository,
        TransactionInterface                     $transaction,
        AccountRepositoryInterface               $accountRepository,
        EsiaRequestPersonDataRepositoryInterface $esiaRequestPersonDataRepository,
        PDO                                      $pdo,
        LoggerInterface                          $logger,
    ): void {


            // Цикл для чекера bankiru
            if (null !== $creditApplicationRepository->findChunkWithRegistrationModeChecker()) {
                $limit = 500;
                $i = 0;
                do {
                    foreach ($creditApplicationRepository->findChunkWithRegistrationModeChecker($i * $limit, $limit) as $creditApplication) {
                        $account = $accountRepository->findByClientId($creditApplication->getClientId());
                        $account->setRegMode(RegistrationMode::makeChecker());
                        $transaction->persist($account)->run();
                    }
                    $i++;
                } while (!empty($creditApplicationRepository->findChunkWithRegistrationModeChecker($i * $limit, $limit)));
            }
            echo 'Аккаунты зарегистрированные через чекер bankiru заполнены';
            echo PHP_EOL;

        // Первый цикл для Esia, перенос из Client
        if (null !== $clientRepository->findChunkRegistrationModeEsia()) {
            $limit = 500;
            $i = 0;
            do {
                foreach ($clientRepository->findChunkRegistrationModeEsia($i * $limit, $limit) as $client) {
                    try {
                        $account = $accountRepository->findByClientId($client->getId());
                        $account->setRegMode(RegistrationMode::makeEsia());
                        $transaction->persist($account)->run();
                    } catch (Exception $exception) {
                        $logger->error('Ошибка с аккаунтом: ID - ' . $client->getId() . 'ошибка: ' . $exception->getMessage());
                        echo PHP_EOL;
                        continue;
                    }
                }
                $i++;
                echo $i * 500 . ' есия акаунтов из первого цикла заполнены.';
                echo PHP_EOL;
            } while (!empty($clientRepository->findChunkRegistrationModeEsia($i * $limit, $limit)));
        }
            echo 'Первая часть аккаунтов зарегистрированных через Esia заполнена';
            echo PHP_EOL;


        // Второй цикл для Esia. Из esia_request_person_data берет дату и ФИ из тела ответа,
        // и если в этот день был зарегестрирован клиент с такими фио то делает запись в reg_mode Account таблицу
            if (null !== $esiaRequestPersonDataRepository->findChunkOfAll()) {
                $limit = 500;
                $i = 0;
                do {
                    foreach ($esiaRequestPersonDataRepository->findChunkOfAll($i * $limit, $limit) as $personData) {
                        try {
                            if ($personData->getResourceType()->isPersonalInfo()) {
                                $client = $clientRepository->findClientWithRegistrationDateAndFI(
                                    json_decode($personData->getResponseData(), true)["lastName"],
                                    json_decode($personData->getResponseData(), true)["firstName"],
                                    $personData->getCreatedAt()
                                );
                            }

                            if ($personData->getResourceType()->isDocInfo()) {
                                $client = $clientRepository->findClientWithRegistrationDateAndPassport(
                                    json_decode($personData->getResponseData(), true)["series"],
                                    json_decode($personData->getResponseData(), true)["number"],
                                    $personData->getCreatedAt()
                                );
                            }

                            if (null !== $client) {
                                $account = $accountRepository->findByClientId($client->getId());
                                $account->setRegMode(RegistrationMode::makeEsia());
                                $transaction->persist($account)->run();
                            }
                        } catch (Exception $exception) {
                            $logger->error('Ошибка с аккаунтом: ID - ' . $client->getId() . 'ошибка: ' . $exception->getMessage());
                            echo PHP_EOL;
                            continue;
                        }
                    }
                    $i++;
                    echo $i * 500 . ' есия акаунтов из второго цикла заполнены.';
                    echo PHP_EOL;
                } while (!empty($esiaRequestPersonDataRepository->findChunkOfAll($i * $limit, $limit)));
            }
        echo 'Вторая часть аккаунтов зарегистрированных через Esia заполнена';
        echo PHP_EOL;


        // Заполняет оставшиеся аккаунты обычным способом регистрации
        $sql = 'UPDATE account
        SET reg_mode = :usualMode
        WHERE `reg_mode` IS NULL';

        $params = [
            ':usualMode' => RegistrationMode::USUAL,
        ];

        $query = $pdo->prepare($sql);
        $query->execute($params);

        echo 'Аккаунты зарегистрированные обычным способом заполнены';
        echo PHP_EOL;
        echo "\nКоманда FillAccountTableRegMode завершена\n\n";
    }
