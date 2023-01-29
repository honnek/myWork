<?php

declare(strict_types=1);

namespace Glavfinans\Core\Loan\Limits;

use Glavfinans\Core\Entity\Charge\ChargeRepositoryInterface;
use Glavfinans\Core\Entity\Commission\CommissionRepositoryInterface;
use Glavfinans\Core\Entity\IncomingTransfer\IncomingTransferRepositoryInterface;
use Glavfinans\Core\Entity\Penalty\PenaltyRepositoryInterface;
use Glavfinans\Core\Loan\PDLConstraints;

/**
 * Фабрика для сервиса LoanLimitsService
 */
class LoanLimitsServiceFactory
{
    /** @var PDLConstraints $pdlConstraints */
    private static PDLConstraints $pdlConstraints;

    /** @var IncomingTransferRepositoryInterface $incomingTransferRepository */
    private static IncomingTransferRepositoryInterface $incomingTransferRepository;

    /** @var PenaltyRepositoryInterface $penaltyRepository */
    private static PenaltyRepositoryInterface $penaltyRepository;

    /** @var ChargeRepositoryInterface $chargeRepository */
    private static ChargeRepositoryInterface $chargeRepository;

    /** @var CommissionRepositoryInterface $commissionRepository */
    private static CommissionRepositoryInterface $commissionRepository;


    /**
     * Установка всех необходимых полей (использоется в DI)
     *
     * @param PDLConstraints $pdlConstraints
     * @param IncomingTransferRepositoryInterface $incomingTransferRepository
     * @param PenaltyRepositoryInterface $penaltyRepository
     * @param ChargeRepositoryInterface $chargeRepository
     * @param CommissionRepositoryInterface $commissionRepository
     * @return void
     */
    public static function setLoanLimitsServiceFactory(
        PDLConstraints                      $pdlConstraints,
        IncomingTransferRepositoryInterface $incomingTransferRepository,
        PenaltyRepositoryInterface          $penaltyRepository,
        ChargeRepositoryInterface           $chargeRepository,
        CommissionRepositoryInterface       $commissionRepository,
    ): void {
        self::$pdlConstraints = $pdlConstraints;
        self::$incomingTransferRepository = $incomingTransferRepository;
        self::$penaltyRepository = $penaltyRepository;
        self::$chargeRepository = $chargeRepository;
        self::$commissionRepository = $commissionRepository;
    }

    /**
     * Создание готового экземпляра класса LoanLimitsService
     *
     * @return LoanLimitsService
     */
    public static function make(): LoanLimitsService
    {
        return new LoanLimitsService(
            self::$pdlConstraints,
            self::$incomingTransferRepository,
            self::$penaltyRepository,
            self::$chargeRepository,
            self::$commissionRepository,
        );
    }
}
