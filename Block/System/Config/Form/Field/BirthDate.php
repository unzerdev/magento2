<?php
declare(strict_types=1);

namespace Unzer\PAPI\Block\System\Config\Form\Field;

use DateTime;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\Locale\Bundle\DataBundle;
use Magento\Framework\Locale\ResolverInterface;

class BirthDate
{
    protected const YEARS_RANGE = 120;

    /**
     * @var DateTime
     */
    private $date;

    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    public function __construct(
        DateTimeFactory $dateTimeFactory,
        ResolverInterface $localeResolver
    ) {
        $this->localeResolver = $localeResolver;
        $this->dateTimeFactory = $dateTimeFactory;
    }

    public function getDay(): string
    {
        return !is_null($this->date) ? $this->date->format('d') : '';
    }

    public function getMonth(): string
    {
        return !is_null($this->date) ? $this->date->format('m') : '';
    }

    public function getYear(): string
    {
        return !is_null($this->date) ? $this->date->format('Y') : '';
    }

    public function getDays(): array
    {
        $days = [];
        for ($index = 1; $index <= 31; $index++) {
            $day = (string)($index < 10 ? '0' . $index : $index);
            $days[$day] = $day;
        }
        return $days;
    }

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

    public function getDate(): ?DateTime
    {
        return $this->date;
    }

    public function setDate($date): self
    {
        if(is_null($date)) {
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
