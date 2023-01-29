<?php

    /**
     * Статистика по комментариям
     *
     * @param ViewFactory $viewFactory
     * @param ExtendedServerRequestInterface $serverRequest
     * @param CommentRepositoryEntityInterface $commentRepository
     * @param IStaffRepository $staffRepository
     * @param IClientRepository $clientRepository
     * @return void
     */
    public function actionCommentStatistics(
        ViewFactory                      $viewFactory,
        ExtendedServerRequestInterface   $serverRequest,
        CommentRepositoryEntityInterface $commentRepository,
        IStaffRepository                 $staffRepository,
        IClientRepository                $clientRepository,
    ): void {
        $parsedBody = $serverRequest->getParsedBody();
        $limit = 30;
        $currentPage = 1;
        $filterDate = '';
        $filterParams = new FilterParamsForComments();

        if ($serverRequest->getParam('page') !== null) {
            $currentPage = $serverRequest->getParam('page') ?? 1;
        }
        $offset = $limit * ($currentPage - 1);
        if (!empty($parsedBody)) {
            $filterParams->setDate($parsedBody['dateOfComment']);
            $filterParams->setNameOfStaff($parsedBody['staffName']);
            $filterParams->setRole($parsedBody['role']);
            $filterParams->setSubString($parsedBody['subString']);
        }
        $commentSearchDTO = new CommentSearchDTO(
            $filterParams->getDate(),
            $filterParams->getNameOfStaff(),
            $filterParams->getRole(),
            $filterParams->getSubString()
        );
        if (null !== $filterParams->getDate()) {
            $filterDate = $filterParams->getDate()->format(DateFmt::D_DB);
        }
        $countPages = ceil($commentRepository->countWithRelations($commentSearchDTO) / $limit);
        $comments = $commentRepository->findWithRelations($commentSearchDTO, $offset, $limit);

        $viewFactory->make('commentStatistics', [
            'staffRepository' => $staffRepository,
            'clientRepository' => $clientRepository,
            'comments' => $comments,
            'limit' => $limit,
            'countPages' => $countPages,
            'currentPage' => $currentPage,
            'dateOfComment' => $filterDate,
            'roles' => Role::getListCodeAndTitle(),
            'staffName' => $serverRequest->getParsedBody()['staffName'] ?? null,
            'filterRole' => Role::getListCodeAndTitle()[$filterParams->getRole()] ?? null,
            'subString' => $filterParams->getSubString() ?? null,
        ])->show(onBootstrap: true);
    }

    /**
     * Отображает список имен в зависимости от выбранной роли
     *
     * @param ExtendedServerRequestInterface $serverRequest
     * @param IStaffRepository $staffRepository
     * @param ViewFactory $viewFactory
     * @return void
     */
    public function actionViewNamesOfStaff(
        ExtendedServerRequestInterface $serverRequest,
        IStaffRepository               $staffRepository,
        ViewFactory                    $viewFactory,
    ) {
        $role = $serverRequest->getParam('role');

        $viewFactory->make('', [])->makeSubview('_searchOfStaff', [
            'names' => $staffRepository->getNameByRole($role),
        ])->show();
    }

    /**
     * Отображает отказные заявки по рекламной рассылке смс
     *
     * string $date Дата смс рассылки
     * string $smsType Тип смс
     * @param ServerRequestInterface $serverRequest
     * @param ViewFactory $viewFactory
     * @throws CHttpException
     */
    public function actionSmsRejectedCreditApplication(
        ServerRequestInterface $serverRequest,
        ViewFactory            $viewFactory
    ) {
        $date = $serverRequest->getQueryParams()['date'] ?? null;
        $smsType = $serverRequest->getQueryParams()['smsType'] ?? null;
        if (null === $date || null === $smsType) {
            throw new CHttpException(400, 'Некорректный запрос.');
        }
        $this->pageTitle = 'Отказные заявки по рекламной рассылке от ' . DateFmt::dateToAppNew($date);

        $date = DateFmt::dateFromDB($date);

        if (false === $date) {
            throw new CHttpException(400, 'Не верный формат запроса');
        }

        $statistics = new SmsSendingStatisticsOnDay($date, $smsType);
        $apps = $statistics->getRejectedApplications();

        $viewFactory->make(
            'rejectedCreditApplication', ['apps' => $apps]
        )->renderPartial('rejectedCreditApplication', ['apps' => $apps]);
    }
