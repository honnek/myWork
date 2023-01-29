 <?php


#[Permission(Staff::CAN_TEMPLATE)]
    public function templates(
        ViewFactory                                                       $viewFactory,
        StaffAndRoleComponent                                             $staffAndRoleComponent,
        StaffInterface                                                    $staff,
        SmsTemplateRepositoryInterface                                    $smsTemplateRepository,
        Paginator                                                         $paginator,
        #[StrParam('sort', required: false, default: 'id')] string        $sort,
        #[StrParam('direction', required: false, default: 'DESC')] string $direction,
        #[IntParam('page', required: false, default: 1)] int              $page,
    ): ViewInterface {
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $department = $staffAndRoleComponent->getDepartmentByRole($staff->getRole()->getCode());

        $fData = function () use ($staff, $smsTemplateRepository, $department, $limit, $offset, $sort, $direction) {
            if ($staff->getRole()->isAdmin()) {
                return $smsTemplateRepository->getBySortLimitOffset($limit, $offset, $sort, $direction);
            }
            return $smsTemplateRepository->getByDepartmentAndSortLimitOffset(
                $department,
                $limit,
                $offset,
                $sort,
                $direction
            );
        };
        $fCount = function () use ($smsTemplateRepository, $department, $staff) {
            if ($staff->getRole()->isAdmin()) {
                return $smsTemplateRepository->getCount();
            }
            return $smsTemplateRepository->getCountByDepartment($department);
        };

        $paginator->fill(new CallbackPaginator($fCount, $fData), limit: $limit, offset: $offset);

        return $viewFactory->makeView('Headquarter/Sms/templatesTable', [
            'paginator' => $paginator,
        ]);
    }

    /**
     * Отрисовка формы для создания нового шаблона смс
     *
     * @param ViewFactory $viewFactory
     * @param FormInterface $createNewSmsForm
     * @return ViewInterface
     */
    #[Permission(Staff::CAN_CREATE_TEMPLATE)]
    public function newTemplate(
        ViewFactory                                                                             $viewFactory,
        #[Form(CreateNewSmsTemplateForm::class)] #[DataClass(SMSTemplate::class)] FormInterface $createNewSmsForm,
    ): ViewInterface {
        return $viewFactory->makeView('Headquarter/Sms/templatesCreate', [
            'newSmsTemplateForm' => $createNewSmsForm->createView(),
        ]);
    }

    /**
     * Создание нового шаблона (Принимает Post запрос)
     * @param FormInterface $createNewSmsForm
     * @param ViewFactory $viewFactory
     * @param ExtendedServerRequestInterface $serverRequest
     * @param FlashMessageInterface $flashMessage
     * @param UrlGeneratorInterface $urlGenerator
     * @return mixed
     */
    #[Permission(Staff::CAN_CREATE_TEMPLATE)]
    public function createTemplate(
        #[Form(CreateNewSmsTemplateForm::class)]
        #[DataClass(CreateNewSmsTemplateFormDTO::class)] FormInterface $createNewSmsForm,
        ViewFactory                                                    $viewFactory,
        ExtendedServerRequestInterface                                 $serverRequest,
        FlashMessageInterface                                          $flashMessage,
        UrlGeneratorInterface                                          $urlGenerator,
    ): ViewInterface|RedirectResponse {
        $serverRequest->assertPost();
        if ($createNewSmsForm->isSubmitted() && $createNewSmsForm->isValid()) {
            $template = new SMSTemplate();
            $template->setClientRole($createNewSmsForm->getData()->getClientRole());
            $template->setCompany($createNewSmsForm->getData()->getCompany());
            $template->setDepartment($createNewSmsForm->getData()->getDepartment());
            $template->setSms($createNewSmsForm->getData()->getSms());
            $template->setMessenger($createNewSmsForm->getData()->getMessenger());
            $template->setSmsType($createNewSmsForm->getData()->getSmsType());

            $template->save(false);
            $flashMessage->pushSuccess('Шаблон успешно создан');

            return new RedirectResponse($urlGenerator->generate('sms_templates'));
        }

        return $viewFactory->makeView('Headquarter/Staff/templatesCreate', [
            'newSmsTemplateForm' => $createNewSmsForm->createView(),
        ]);
    }

    /**
     * Редактирование шаблона СМС
     * @param FormInterface $editSmsTemplateForm
     * @param ViewFactory $viewFactory
     * @param FlashMessageInterface $flashMessage
     * @param UrlGeneratorInterface $urlGenerator
     * @return RedirectResponse|ViewInterface
     */
    #[Permission(Staff::CAN_UPDATE_TEMPLATE)]
    public function updateTemplate(
        #[Form(CreateNewSmsTemplateForm::class)]
        #[DataClass(SMSTemplate::class, repository: SmsTemplateRepositoryInterface::class)] FormInterface $editSmsTemplateForm,
        ViewFactory                                                                                       $viewFactory,
        FlashMessageInterface                                                                             $flashMessage,
        UrlGeneratorInterface                                                                             $urlGenerator,
    ): RedirectResponse|ViewInterface {
        if ($editSmsTemplateForm->isSubmitted() && $editSmsTemplateForm->isValid()) {
            $template = $editSmsTemplateForm->getData();
            $template->save(false);
            $flashMessage->pushSuccess('Шаблон успешно обновлен');

            return new RedirectResponse($urlGenerator->generate('sms_templates'));
        }

        return $viewFactory->makeView('Headquarter/Sms/templatesEdit', [
            'editSmsTemplateForm' => $editSmsTemplateForm->createView(),
        ]);
    }

    /**
     * Удаление шаблона смс
     * @param ExtendedServerRequestInterface $serverRequest
     * @param FlashMessageInterface $flashMessage
     * @param SmsTemplateRepositoryInterface $smsTemplateRepository
     * @param UrlGeneratorInterface $urlGenerator
     * @return RedirectResponse
     * @throws CDbException
     */
    #[Permission(Staff::CAN_DELETE_TEMPLATE)]
    public function deleteTemplate(
        ExtendedServerRequestInterface $serverRequest,
        FlashMessageInterface          $flashMessage,
        SmsTemplateRepositoryInterface $smsTemplateRepository,
        UrlGeneratorInterface          $urlGenerator,
    ): RedirectResponse {
        $id = $serverRequest->getIntParamOrFail('id');

        $template = $smsTemplateRepository->findByPK($id);
        if (null === $template) {
            $flashMessage->pushError('Шаблон не найден');
        } elseif ($template->delete()) {
            $flashMessage->pushSuccess('Шаблон удален');
        } else {
            $flashMessage->pushError('Ошибка удаления шаблона');
        }

        return new RedirectResponse($urlGenerator->generate('sms_templates'));
    }

