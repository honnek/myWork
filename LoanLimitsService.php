<?php

declare(strict_types=1);

namespace Glavfinans\Core\Loan\Limits;

use DateFmt;
use DateTimeImmutable;
use DateTimeInterface;
use Glavfinans\Core\Entity\Charge\ChargeRepositoryInterface;
use Glavfinans\Core\Entity\Commission\CommissionRepositoryInterface;
use Glavfinans\Core\Entity\IncomingTransfer\IncomingTransferRepositoryInterface;
use Glavfinans\Core\Entity\Penalty\PenaltyRepositoryInterface;
use Glavfinans\Core\Loan\PDLConstraints;
use Glavfinans\Core\Money;
use IncomingTransfer;
use LogicException;

/**
 * Сервис для работы с пределами начислений
 */
class LoanLimitsService
{
    /**
     * @param PDLConstraints $pdlConstraints
     * @param IncomingTransferRepositoryInterface $incomingTransferRepository
     * @param PenaltyRepositoryInterface $penaltyRepository
     * @param ChargeRepositoryInterface $chargeRepository
     * @param CommissionRepositoryInterface $commissionRepository
     */
    public function __construct(
        protected PDLConstraints                      $pdlConstraints,
        protected IncomingTransferRepositoryInterface $incomingTransferRepository,
        protected PenaltyRepositoryInterface          $penaltyRepository,
        protected ChargeRepositoryInterface           $chargeRepository,
        protected CommissionRepositoryInterface       $commissionRepository,
    ) {
    }

    /**
     * Возвращает максимальную сумму, которую можно начислить
     *
     * @param LoanLimitsDTO $dto
     * @param DateTimeInterface|null $date
     * @return Money
     */
    public function getMaxPercentSum(LoanLimitsDTO $dto, ?DateTimeInterface $date = null): Money
    {
        $date ??= new DateTimeImmutable();
        $issueSum = $dto->getFirstSum();

        if (null === $dto->getIssueDate()) {
            throw new LogicException(message: 'Не найдена дата выдачи займа, loanId: ' . $dto->getLoanId());
        }

        $maxSum = $issueSum * $this->pdlConstraints->getMaxX($dto->getIssueDate());
        $percentPaid = 0;
        $destinations = [
            IncomingTransfer::PERCENT,
            IncomingTransfer::COMMISSION_COLLECT,
            IncomingTransfer::COMMISSION_PROLONG,
            IncomingTransfer::COMMISSION_PAYMENT,
        ];

        foreach ($destinations as $destination) {
            $percentPaid += $this->incomingTransferRepository->sumIncomingToDate($date, $destination, $dto->getLoanId())->getRub();
        }

        if ($percentPaid > 0) {
            $maxSum += $percentPaid;
            $maxSumX = $issueSum * $this->pdlConstraints->getMaxX($dto->getIssueDate());
            if ($maxSum > $maxSumX) {
                $maxSum = $maxSumX;
            }
        }

        return Money::fromRub($maxSum);
    }

    /**
     * Сумма начисленных процентов, коммисии по гфк и пени в зависимости от даты
     *
     * @param LoanLimitsDTO $dto
     * @param DateTimeInterface|null $date
     * @return int
     */
    public function getTotalChargedByX(LoanLimitsDTO $dto, DateTimeInterface $date = null): int
    {
        $date ??= new DateTimeImmutable();
        $dateFromX2_3 = DateFmt::fromDB('2017-01-01 00:00:00');

        $sum = $this->chargeRepository->getSumChargedPercent($dto->getLoanId(), $date) +
            (int)$this->commissionRepository->sumChargedCommissionGFK($dto->getLoanId(), $date)->getRub();

        if ($dto->getIssueDate() >= $dateFromX2_3) {
            $sum += $this->penaltyRepository->getSumPenaltyForPaid($dto->getLoanId());
        }

        return $sum;
    }


    /**
     * Возвращает максимальное количество начислений которые теоретически можно начислить
     *
     * @param LoanLimitsDTO $dto
     * @return int
     */
    public function getMaxXSum(LoanLimitsDTO $dto): int
    {
        if (null === $dto->getIssueDate()) {
            throw new LogicException(message: 'Не найдена дата выдачи займа, loanId: ' . $dto->getLoanId());
        }

        return (int)($dto->getFirstSum() * $this->pdlConstraints->getMaxX($dto->getIssueDate()));
    }

}
