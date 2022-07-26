<?php

use PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn;
use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class OrderListNote extends Module
{
    /** @var array */
    public const MODULE_HOOKS = [
        'actionOrderGridDefinitionModifier',
        'actionOrderGridQueryBuilderModifier',
    ];

    /** @var string */
    public const CUSTOMER_FIELD_NOTE = 'customer_note';

    public function __construct()
    {
        $this->name = 'orderlistnote';
        $this->author = 'Quentin L.';
        $this->version = '1.0.0';
        $this->ps_versions_compliancy = ['min' => '1.7.7.0', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->l('Private note on the orders list');
        $this->description = $this->l('Displays your private notes on your orders list in back office');
    }

    /**
     * Installer.
     *
     * @return bool
     */
    public function install()
    {
        return parent::install()
            && $this->registerHook(static::MODULE_HOOKS);
    }

    /**
     * Modifies Order list Grid.
     *
     * @return void
     */
    public function hookActionOrderGridDefinitionModifier(array $params)
    {
        /** @var \PrestaShop\PrestaShop\Core\Grid\Definition\GridDefinitionInterface $definition */
        $definition = $params['definition'];

        $definition
            ->getColumns()
            ->addAfter(
                'id_order',
                (new DataColumn(static::CUSTOMER_FIELD_NOTE))
                    ->setName($this->l('Note'))
                    ->setOptions([
                        'field' => static::CUSTOMER_FIELD_NOTE,
                ])
            )
        ;

        $filters = $definition->getFilters();
        $filters->add((new Filter(static::CUSTOMER_FIELD_NOTE, TextType::class))
            ->setTypeOptions([
                'required' => false,
            ])
            ->setAssociatedColumn(static::CUSTOMER_FIELD_NOTE)
        );
    }

    /**
     * Handle order list queries and filters.
     *
     * @return void
     */
    public function hookActionOrderGridQueryBuilderModifier(array $params)
    {
        /** @var \Doctrine\DBAL\Query\QueryBuilder $queryBuilder */
        $queryBuilder = $params['search_query_builder'];

        /** @var \PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface */
        $searchCriteria = $params['search_criteria'];

        $queryBuilder->addSelect(
            'IF(customer.note = "0", "'.Configuration::get('PS_SHOP_NAME').'", customer.note) customer_note'
        );

        $queryBuilder->leftJoin('o', _DB_PREFIX_.'customer', 'customer', 'o.id_customer = customer.id_customer');

        if (static::CUSTOMER_FIELD_NOTE === $searchCriteria->getOrderBy()) {
            $queryBuilder->orderBy(static::CUSTOMER_FIELD_NOTE, $searchCriteria->getOrderWay());
        }

        foreach ($searchCriteria->getFilters() as $filterName => $filterValue) {
            if (static::CUSTOMER_FIELD_NOTE === $filterName) {
                $queryBuilder->having(static::CUSTOMER_FIELD_NOTE.' LIKE :'.static::CUSTOMER_FIELD_NOTE);
                $queryBuilder->setParameter(static::CUSTOMER_FIELD_NOTE, '%'.$filterValue.'%');
            }
        }
    }
}
