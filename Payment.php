
    /**
     * Удаление платежей и открытие займов
     *
     * @param ExtendedServerRequestInterface $serverRequest
     * @param Godzilla $godzilla
     * @param LoanRepositoryInterface $loanRepository
     * @param PaymentRepository $paymentRepository
     * @param FlashMessageInterface $flashMessage
     * @param CommentService $commentService
     * @param CommentFactory $commentFactory
     * @param TransactionInterface $transaction
     * @return void
     * @throws BaseException
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     * @throws HttpRequestException
     * @throws NotFoundException
     * @throws Throwable
     */
    #[Permission(Staff::CAN_DELETE_PAYMENTS_FOR_LOAN_MODAL)]
    public function actionDeletePaymentAndOpenLoan(
        ExtendedServerRequestInterface $serverRequest,
        Godzilla                       $godzilla,
        LoanRepositoryInterface        $loanRepository,
        PaymentRepository              $paymentRepository,
        FlashMessageInterface          $flashMessage,
        CommentService                 $commentService,
        CommentFactory                 $commentFactory
        CommentFactory                 $commentFactory,
    ): void {
        $begin = $serverRequest->getParamOrFail('begin', 'POST');
        $end = $serverRequest->getParamOrFail('end', 'POST');
        $loanId = $serverRequest->getParamOrFail('loanId', 'POST');
        $loan = $loanRepository->findByPk($loanId) ?? throw new NotFoundException("Не найден займ #$loanId");
        $client = $loan->getClient();

        if ($client->hasCurrentLoan()) {
            throw new LogicException("У клиента #{$client->getId()} не может быть два активных займа");
        }

        $beginDate = new DateTimeImmutable($begin);
        $endDate = new DateTimeImmutable($end);

        /** @var Payment[] $payments */
        $payments = $paymentRepository->findAllIncomingPaymentsByLoanId($loanId, $beginDate, $endDate);
        $text = '';
        $i = 1;

        /** @var CDbTransaction $transaction */
        $transaction = Yii::app()->db->beginTransaction();
        foreach ($payments as $key => $payment) {
        foreach ($payments as $payment) {
            $text .= "$i. Платеж с system_id: {$payment->getSystemId()}, sum: {$payment->getSum()->getRub()},
                      payment_date: {$payment->getPaymentDate()->format(DateFmt::DT_DB)}, 
                      system: {$payment->getType()->getValue()}, rrn: {$payment->getRrn()}, card_number: {$payment->getCardNumber()}" . PHP_EOL;
            if (!$payment->delete()) {
                $transaction->rollback();
                throw new DomainException('Ошибка удаления платежа!', 500);
            }

            $i++;
        }
        $transaction->commit();

        $godzilla->recalc($loanId, ['Nothing'], Recalc::REASON_CB);

        $client->setLastLoanId($loanRepository->findLastIdByClientId($client->getId()));
        $client->save(false, ['last_loan_id']);
        $comment = $commentFactory->makeWarning($client->getId(), "Удалены платежи у займа #{$loanId}:" . PHP_EOL . $text);
        $commentService->add($comment);
        $flashMessage->pushSuccess('Удаление платежей и открытие займа прошло успешно');

        $this->redirect($serverRequest->getHttpReferer());
    }

    /**
     * Удаление платежа
     *
     * @throws CDbException
     * @throws HttpRequestException
     * @throws Throwable
     */
    #[Permission(Staff::CAN_DELETE_PAYMENT)]
    public function actionDeletePayment(
        ExtendedServerRequestInterface $serverRequest,
        PaymentRepositoryInterface     $paymentRepository,
        Godzilla                       $godzilla,
        TransactionInterface           $transaction,
        LoanRepository                 $loanRepository,
        FlashMessageInterface          $flashMessage,
    ) {
        $id = $serverRequest->getParamOrFail('id');
        /** @var Glavfinans\Core\Entity\Payment\Payment $payment */
        $payment = $paymentRepository->findByPk($id) ?? throw new NotFoundException("Платеж с id: $id не найден");
        $loan = $loanRepository->findByPk($payment->getLoanId())
            ?? throw new NotFoundException(message: "Займ #{$payment->getLoanId()} не найден при удалении платежа");

        if ($loan->getStatus()->isActive()) {
            $payment->setDeletedAt(new DateTimeImmutable());
            $transaction->persist($payment)->run();

            $godzilla->recalc($loan->getId(), ['Nothing'], Recalc::REASON_CB);

            $flashMessage->pushSuccess("Платеж на сумму {$payment->getSum()->getRub()} рублей успешно удален");
        } else {
            $flashMessage->pushError('Нельзя удалить платеж у завершенного займа');
        }

        $this->redirect($serverRequest->getHttpReferer());
    }
