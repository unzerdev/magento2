<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\Checkout\Data;

use Unzer\PAPI\Api\Data\AddressInterface;
use Unzer\PAPI\Api\Data\AddressInterfaceFactory;
use Unzer\PAPI\Api\Data\CompanyInfoInterface;
use Unzer\PAPI\Api\Data\CompanyInfoInterfaceFactory;
use Unzer\PAPI\Api\Data\CustomerInterface;
use Unzer\PAPI\Model\Source\Customer as CustomerResource;

/**
 * Checkout API Customer DTO.
 *
 * @link  https://docs.unzer.com/
 */
class Customer implements CustomerInterface
{
    /** @var string|null $id */
    protected ?string $id;

    /** @var string|null $firstname */
    protected ?string $firstname;

    /** @var string|null $lastname */
    protected ?string $lastname;

    /** @var string|null $salutation */
    protected ?string $salutation;

    /** @var string|null $birthDate */
    protected ?string $birthDate;

    /** @var string|null $company */
    protected ?string $company;

    /** @var string|null $email */
    protected ?string $email;

    /** @var string|null $phone */
    protected ?string $phone;

    /** @var string|null $mobile */
    protected ?string $mobile;

    /** @var AddressInterface|null $billingAddress */
    protected ?AddressInterface $billingAddress;

    /** @var AddressInterface|null $shippingAddress */
    protected ?AddressInterface $shippingAddress;

    /** @var CompanyInfoInterface|null $companyInfo */
    protected ?CompanyInfoInterface $companyInfo = null;

    /** @var String|null $threatMetrixId */
    protected ?string $threatMetrixId;

    /** @var AddressInterfaceFactory  */
    private AddressInterfaceFactory $addressInterfaceFactory;

    /** @var CompanyInfoInterfaceFactory */
    private CompanyInfoInterfaceFactory $companyInfoInterfaceFactory;

    /**
     * Constructor
     *
     * @param AddressInterfaceFactory $addressInterfaceFactory
     * @param CompanyInfoInterfaceFactory $companyInfoInterfaceFactory
     */
    public function __construct(
        AddressInterfaceFactory $addressInterfaceFactory,
        CompanyInfoInterfaceFactory $companyInfoInterfaceFactory
    ) {
        $this->addressInterfaceFactory = $addressInterfaceFactory;
        $this->companyInfoInterfaceFactory = $companyInfoInterfaceFactory;
    }

    /**
     * From Resource
     *
     * @param CustomerResource $customerResource
     * @return self
     */
    public function fromResource(CustomerResource $customerResource): self
    {
        $this->id = $customerResource->getId();
        $this->firstname = $customerResource->getFirstname();
        $this->lastname = $customerResource->getLastname();
        $this->salutation = $customerResource->getSalutation();
        $this->birthDate = $customerResource->getBirthDate();
        $this->company = $customerResource->getCompany();
        $this->email = $customerResource->getEmail();
        $this->phone = $customerResource->getPhone();
        $this->mobile = $customerResource->getMobile();
        $this->threatMetrixId = $customerResource->getThreatMetrixId();

        if ($customerResource->getBillingAddress() !== null) {
            $this->billingAddress = $this->addressInterfaceFactory->create();
            $this->billingAddress->fromResource($customerResource->getBillingAddress());
        }

        if ($customerResource->getShippingAddress() !== null) {
            $this->shippingAddress = $this->addressInterfaceFactory->create();
            $this->shippingAddress->fromResource($customerResource->getShippingAddress());
        }

        if ($customerResource->getCompanyInfo() !== null) {
            $this->companyInfo = $this->companyInfoInterfaceFactory->create();
            $this->companyInfo->fromResource($customerResource->getCompanyInfo());
        }

        return $this;
    }

    /**
     * Get ID
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Get Firstname
     *
     * @return string|null
     */
    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    /**
     * Get Lastname
     *
     * @return string|null
     */
    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    /**
     * Get Salutation
     *
     * @return string|null
     */
    public function getSalutation(): ?string
    {
        return $this->salutation;
    }

    /**
     * Get BirthDate
     *
     * @return string|null
     */
    public function getBirthDate(): ?string
    {
        return $this->birthDate;
    }

    /**
     * Get Company
     *
     * @return string|null
     */
    public function getCompany(): ?string
    {
        return $this->company;
    }

    /**
     * Get Email
     *
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Get Phone
     *
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * Get Mobile
     *
     * @return string|null
     */
    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    /**
     * Get Billing Address
     *
     * @return \Unzer\PAPI\Api\Data\AddressInterface|null
     */
    public function getBillingAddress(): ?\Unzer\PAPI\Api\Data\AddressInterface
    {
        return $this->billingAddress;
    }

    /**
     * Get Shipping Address
     *
     * @return \Unzer\PAPI\Api\Data\AddressInterface|null
     */
    public function getShippingAddress(): ?\Unzer\PAPI\Api\Data\AddressInterface
    {
        return $this->shippingAddress;
    }

    /**
     * Get Company Info
     *
     * @return \Unzer\PAPI\Api\Data\CompanyInfoInterface|null
     */
    public function getCompanyInfo(): ?\Unzer\PAPI\Api\Data\CompanyInfoInterface
    {
        return $this->companyInfo;
    }

    /**
     * Get ThreatMetrixId
     *
     * @return string|null
     */
    public function getThreatMetrixId(): ?string
    {
        return $this->threatMetrixId;
    }
}
