<?php

/**
 * Тесты для методов класса RegistrationMode
 */
class RegistrationModeTest extends TestCase
{

    /**
     * Тест успешного создания флагов
     *
     * @covers RegistrationMode::__construct
     */
    public function testCreation()
    {
        foreach (RegistrationMode::getCodeList() as $code) {
            $activity = new RegistrationMode($code);
            $this->assertEquals($code, $activity->getCode());
        }
    }

    /**
     * Тест неудачного создания флагов
     *
     * @covers RegistrationMode::__construct
     */
    public function testFailCreation()
    {
        $this->expectException(OutOfBoundsException::class);
        new RegistrationMode(-1);
    }

    /**
     * Тест метода getCode
     *
     * @covers RegistrationMode::getCode
     */
    public function testGetCode()
    {
        foreach (RegistrationMode::getCodeList() as $code) {
            $registrationMode = new RegistrationMode($code);
            $this->assertEquals($code, $registrationMode->getCode());
        }
    }

    /**
     * Тест метода getCodeList
     *
     * @covers RegistrationMode::getCodeList
     */
    public function testGetCodeList()
    {
        $codeList = RegistrationMode::getCodeList();
        $this->assertGreaterThan(0, count($codeList));

        foreach ($codeList as $code) {
            $this->assertIsInt($code);
        }
    }

    /**
     * Тест метода getList
     *
     * @covers RegistrationMode::getList
     */
    public function testGetList()
    {
        $list = RegistrationMode::getList();
        $codeList = RegistrationMode::getCodeList();
        $this->assertGreaterThan(0, count($list));

        foreach ($list as $value) {
            $this->assertContains($value->getCode(), $codeList);
        }

        $this->assertEquals(count($list), count($codeList));
    }

    /**
     * Тест метода getAssocList
     *
     *
     * @covers RegistrationMode::getAssocList
     */
    public function testGetAssocList()
    {
        $aliasList = RegistrationMode::getAssocList();

        /** Проверяем что все значения в массиве имеют тип string */
        foreach ($aliasList as $alias) {
            $this->assertIsString(actual: $alias);
        }
    }

    /**
     * Тест метода getTittle
     *
     * @covers RegistrationMode::getTitle
     */
    public function testGetTittle()
    {
        foreach (RegistrationMode::getAssocList() as $code => $title) {
            $registrationMode = new RegistrationMode($code);
            $this->assertEquals($title, $registrationMode->getTitle());
        }
    }

    /**
     * Тест метода isEqual
     *
     * @covers RegistrationMode::isEqual
     */
    public function testIsEqual()
    {
        $esia = new RegistrationMode(RegistrationMode::ESIA);
        $checker = new RegistrationMode(RegistrationMode::CHECKER);

        $this->assertTrue($esia->isEqual(clone $esia));
        $this->assertFalse($esia->isEqual($checker));
    }

    /**
     * Тест метода isUsual
     *
     * @covers RegistrationMode::isUsual
     */
    public function testIsUsual()
    {
        $usual = new RegistrationMode(RegistrationMode::USUAL);
        $esia = new RegistrationMode(RegistrationMode::ESIA);

        $this->assertTrue($usual->isUsual());
        $this->assertFalse($esia->isUsual());
    }

    /**
     * Тест метода IsEsia
     *
     * @covers RegistrationMode::isEsia
     */
    public function testIsEsia()
    {
        $esia = new RegistrationMode(RegistrationMode::ESIA);
        $checker = new RegistrationMode(RegistrationMode::CHECKER);

        $this->assertTrue($esia->isEsia());
        $this->assertFalse($checker->isEsia());
    }

    /**
     * Тест метода isChecker
     *
     * @covers RegistrationMode::isChecker
     */
    public function testIsChecker()
    {
        $checker = new RegistrationMode(RegistrationMode::CHECKER);
        $usual = new RegistrationMode(RegistrationMode::USUAL);

        $this->assertTrue($checker->isChecker());
        $this->assertFalse($usual->isChecker());
    }

    /**
     * Тест метода makeUsual
     *
     * @covers RegistrationMode::makeUsual
     */
    public function testMakeUsual()
    {
        $this->assertEquals(
            new RegistrationMode(RegistrationMode::USUAL),
            RegistrationMode::makeUsual()
        );
        $this->assertNotEquals(
            new RegistrationMode(RegistrationMode::ESIA),
            RegistrationMode::makeUsual()
        );
    }

    /**
     * Тест метода makeEsia
     *
     * @covers RegistrationMode::makeEsia
     */
    public function testMakeEsia()
    {
        $this->assertEquals(
            new RegistrationMode(RegistrationMode::ESIA),
            RegistrationMode::makeEsia()
        );
        $this->assertNotEquals(
            new RegistrationMode(RegistrationMode::CHECKER),
            RegistrationMode::makeEsia()
        );
    }

    /**
     * Тест метода makeChecker
     *
     * @covers RegistrationMode::makeChecker
     */
    public function testMakeChecker()
    {
        $this->assertEquals(
            new RegistrationMode(RegistrationMode::CHECKER),
            RegistrationMode::makeChecker()
        );
        $this->assertNotEquals(
            new RegistrationMode(RegistrationMode::USUAL),
            RegistrationMode::makeChecker()
        );
    }
}
