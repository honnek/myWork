<?php

/**
 * Value Object описывающий способы регистрации
 */
class RegistrationMode implements ValueInterface
{
    /** @var int $code - Числовой код способа регистрации */
    protected int $code;

    /** @var int Обычный способ регистрации */
    public const USUAL = 1;

    /** @var int Способ регистрации через ЕСИА */
    public const ESIA = 2;

    /** @var int Способ регистрации через банки ру чекер */
    public const CHECKER = 3;

    /**
     * Конструктор RegistrationMode
     * @param int $code
     */
    public function __construct(int $code)
    {
        if (!in_array($code, $this->getCodeList())) {
            throw new OutOfBoundsException('Невозможно создать объект способа регистрации c кодом ' . $code);
        }

        $this->code = $code;
    }

    /**
     * Возвращает код объекта
     *
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * Возвращает список всех допустимых кодов
     *
     * @return int[]
     */
    public static function getCodeList(): array
    {
        return [
            self::USUAL,
            self::ESIA,
            self::CHECKER,
        ];
    }

    /**
     * Возвращает список с возможными способами регистрации и их наименованиями
     *
     * @return string[]
     */
    public static function getAssocList(): array
    {
        return [
            self::USUAL => 'Обычная регистрация',
            self::ESIA => 'Регистрация через ЕСИА',
            self::CHECKER => 'Регистрация через Банки Ру чекер',
        ];
    }

    /**
     * Возвращает название
     * @return string
     */
    public function getTitle(): string
    {
        return self::getAssocList()[$this->getCode()];
    }

    /**
     * Возвращает список всех объектов способов регистрации
     *
     * @return RegistrationMode[]
     */
    public static function getList(): array
    {
        return [
            self::makeUsual(),
            self::makeEsia(),
            self::makeChecker(),
        ];
    }

    /**
     * Сравнение значений способов регистрации
     *
     * @param RegistrationMode $registrationMode
     * @return bool
     */
    public function isEqual(RegistrationMode $registrationMode): bool
    {
        return $this->getCode() === $registrationMode->getCode();
    }

    /**
     * Проверяет является ли способ регистрации - обычный
     *
     * @return bool
     */
    public function isUsual(): bool
    {
        return self::USUAL === $this->code;
    }

    /**
     * Проверяет является ли способ регистрации - через ЕСИА
     *
     * @return bool
     */
    public function isEsia(): bool
    {
        return self::ESIA === $this->code;
    }

    /**
     * Проверяет является ли способ регистрации - через Банки ру чекер
     *
     * @return bool
     */
    public function isChecker(): bool
    {
        return self::CHECKER === $this->code;
    }

    /**
     * Создает и возвращает объект RegistrationMode с кодом USUAL
     *
     * @return RegistrationMode
     */
    public static function makeUsual(): RegistrationMode
    {
        return new RegistrationMode(code: RegistrationMode::USUAL);
    }

    /**
     * Создает и возвращает объект RegistrationMode с кодом ESIA
     *
     * @return RegistrationMode
     */
    public static function makeEsia(): RegistrationMode
    {
        return new RegistrationMode(code: RegistrationMode::ESIA);
    }

    /**
     * Создает и возвращает объект RegistrationMode с кодом CHECKER
     *
     * @return RegistrationMode
     */
    public static function makeChecker(): RegistrationMode
    {
        return new RegistrationMode(code: RegistrationMode::CHECKER);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->code;
    }

    /**
     * @return string
     */
    public function rawValue(): string
    {
        return $this->__toString();
    }

    /**
     * @return int
     */
    public function rawType(): int
    {
        return PDO::PARAM_STR;
    }

    /**
     * @param $value
     * @param DatabaseInterface $db
     * @return static
     */
    public static function typecast($value, DatabaseInterface $db): self
    {
        return new static($value);
    }

}
