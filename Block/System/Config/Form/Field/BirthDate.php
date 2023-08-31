<?php
declare(strict_types=1);

namespace Unzer\PAPI\Block\System\Config\Form\Field;

use DateTime;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\Locale\Bundle\DataBundle;
use Magento\Framework\Locale\ResolverInterface;

/**
 * Customer Account Order Prepayment Information Block
 *
 * Copyright (C) 2021 - today Unzer GmbH
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @link  https://docs.unzer.com/
 */
class BirthDate
{
    protected const YEARS_RANGE = 120;

    /**
     * @var DateTime|null
     */
    private ?DateTime $date = null;

    /**
     * @var ResolverInterface
     */
    private ResolverInterface $localeResolver;

    /**
     * @var DateTimeFactory
     */
    private DateTimeFactory $dateTimeFactory;

    /**
     * Constructor
     *
     * @param DateTimeFactory $dateTimeFactory
     * @param ResolverInterface $localeResolver
     */
    public function __construct(
        DateTimeFactory $dateTimeFactory,
        ResolverInterface $localeResolver
    ) {
        $this->localeResolver = $localeResolver;
        $this->dateTimeFactory = $dateTimeFactory;
    }

    /**
     * Get Day
     *
     * @return string
     */
    public function getDay(): string
    {
        return $this->date !== null ? $this->date->format('d') : '';
    }

    /**
     * Get Month
     *
     * @return string
     */
    public function getMonth(): string
    {
        return $this->date !== null ? $this->date->format('m') : '';
    }

    /**
     * Get Year
     *
     * @return string
     */
    public function getYear(): string
    {
        return $this->date !== null ? $this->date->format('Y') : '';
    }

    /**
     * Get Days
     *
     * @return array
     */
    public function getDays(): array
    {
        $days = [];
        for ($index = 1; $index <= 31; $index++) {
            $day = (string)($index < 10 ? '0' . $index : $index);
            $days[$day] = $day;
        }
        return $days;
    }

    /**
     * Get Months
     *
     * @return array
     */
    public function getMonths(): array
    {
        $data = [];
        $months = (new DataBundle())->get(
            $this->localeResolver->getLocale()
        )['calendar']['gregorian']['monthNames']['format']['wide'];
        foreach ($months as $key => $value) {
            $monthNum = (string)(++$key < 10 ? '0' . $key : $key);
            $data[$monthNum] = $monthNum . ' - ' . $value;
        }
        return $data;
    }

    /**
     * Get Years
     *
     * @return array
     */
    public function getYears(): array
    {
        $years = [];
        $first = (int)date('Y');
        for ($index = $first; $index >= ($first - self::YEARS_RANGE); $index--) {
            $year = (string)$index;
            $years[$year] = $year;
        }
        return $years;
    }

    /**
     * Get Date
     *
     * @return DateTime|null
     */
    public function getDate(): ?DateTime
    {
        return $this->date;
    }

    /**
     * Set Date
     *
     * @param mixed $date
     * @return $this
     */
    public function setDate($date): self
    {
        if ($date === null) {
            $date = '';
        }

        if (is_string($date) && $date !== '') {
            $this->date = $this->dateTimeFactory->create($date);
        }

        if (is_array($date)
            && array_key_exists('year', $date)
            && array_key_exists('month', $date)
            && array_key_exists('day', $date)) {

            $this->date = $this->dateTimeFactory->create($date['year'] . '-' . $date['month'] . '-' . $date['day']);
        }

        if ($date instanceof DateTime) {
            $this->date = $date;
        }

        return $this;
    }
}
