<?php

namespace Glavfinans\Core\Staff;

use Staff;

/**
 * Компонента для Staff и Role
 */
class StaffAndRoleComponent
{
    /**
     * Получаем Департамент текущего пользователя
     *
     * @param $staffRole
     * @return string
     */
    public function getDepartmentByRole($staffRole): string
    {
        return match ($staffRole) {
            Role::MARKETER => Staff::DEPARTMENT_MARKETING,
            Role::HEAD_VERIFIER => Staff::DEPARTMENT_HEAD_VERIFIER,
            Role::VERIFIER, Role::SENIOR_VERIFIER => Staff::DEPARTMENT_VERIFIER,
            Role::COLLECTOR_CHIEF => Staff::DEPARTMENT_COLLECTOR,
            Role::COLLECTOR, Role::SENIOR_COLLECTOR => Staff::DEPARTMENT_HARD_COLLECTOR,
            Role::JURIST => Staff::DEPARTMENT_JURIST,
            Role::CALL_CENTER, Role::CALL_CENTER_EXTENDED => Staff::DEPARTMENT_CALL_CENTER,
            Role::SOFT_COLLECTOR => Staff::DEPARTMENT_SOFT_COLLECTOR,
            Role::ADMIN => Staff::DEPARTMENT_ALL,
            default => Staff::DEPARTMENT_NO,
        };
    }
}
