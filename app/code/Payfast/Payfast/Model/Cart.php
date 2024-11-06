<?php
/**
 * Copyright (c) 2024 Payfast (Pty) Ltd
 */

namespace Payfast\Payfast\Model;

use Magento\Payment\Model\Cart\SalesModel\SalesModelInterface;

/**
 * Payfast-specific model for shopping cart items and totals
 * The main idea is to accommodate all possible totals into Payfast-compatible 4 totals and line items
 */
class Cart extends \Magento\Payment\Model\Cart
{
    /**
     * @var bool
     */
    protected bool $_areAmountsValid = false;

    /**
     * Get shipping, tax, subtotal and discount amounts all together
     *
     * @return array
     */
    public function getAmounts(): array
    {
        // Call the parent method if applicable
        parent::getAmounts();

        $this->_collectItemsAndAmounts();

        if (!$this->_areAmountsValid) {
            $subtotal = $this->_calculateSubtotal();

            return [self::AMOUNT_SUBTOTAL => $subtotal];
        }

        return $this->_amounts;
    }

    private function _calculateSubtotal(): float
    {
        $subtotal = $this->getSubtotal() + $this->getTax();

        if (empty($this->_transferFlags[self::AMOUNT_SHIPPING])) {
            $subtotal += $this->getShipping();
        }

        if (empty($this->_transferFlags[self::AMOUNT_DISCOUNT])) {
            $subtotal -= $this->getDiscount();
        }

        return $subtotal;
    }


    /**
     * Check whether any item has negative amount
     *
     * @return bool
     */
    public function hasNegativeItemAmount(): bool
    {
        foreach ($this->_customItems as $item) {
            if ($item->getAmount() < 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate subtotal from custom items
     *
     * @return void
     */
    protected function _calculateCustomItemsSubtotal(): void
    {
        parent::_calculateCustomItemsSubtotal();
        $this->_applyDiscountTaxCompensationWorkaround($this->_salesModel);

        $this->_validate();
    }

    /**
     * Check the line items and totals according to Payfast business logic limitations
     *
     * @return void
     */
    protected function _validate(): void
    {
        $areItemsValid          = false;
        $this->_areAmountsValid = false;

        $referenceAmount = $this->_salesModel->getDataUsingMethod('base_grand_total');
        $itemsSubtotal = $this->_calculateItemsSubtotal();

        $sum = $itemsSubtotal + $this->getTax();
        $sum = $this->_applyShippingAndDiscounts($sum, $itemsSubtotal);

        /**
         * Numbers are intentionally converted to strings because of possible comparison error
         * see http://php.net/float
         */
        if (sprintf('%.4F', $sum) == sprintf('%.4F', $referenceAmount)) {
            $areItemsValid = true;
        }

        $areItemsValid = $areItemsValid && $this->_areAmountsValid;

        if (!$areItemsValid) {
            $this->_salesModelItems = [];
            $this->_customItems     = [];
        }
    }

    private function _calculateItemsSubtotal(): float
    {
        $itemsSubtotal = 0;
        foreach ($this->getAllItems() as $i) {
            $itemsSubtotal += $i->getQty() * $i->getAmount();
        }
        return $itemsSubtotal;
    }

    private function _applyShippingAndDiscounts(float $sum, float $itemsSubtotal): float
    {
        $sum = $this->_applyAdjustment($sum);

        return $this->_applyDiscount($sum, $itemsSubtotal);
    }

    private function _applyAdjustment(float $sum): float
    {
        if (empty($this->_transferFlags[self::AMOUNT_SHIPPING])) {
            if ('shipping' === 'shipping') {
                $sum += $this->getShipping();
            } elseif ('shipping' === 'discount') {
                $sum -= $this->getDiscount();
            }
        }
        return $sum;
    }

    private function _applyDiscount(float $sum, float $itemsSubtotal): float
    {
        if (empty($this->_transferFlags[self::AMOUNT_DISCOUNT])) {
            $sum -= $this->getDiscount();
            $this->_areAmountsValid = round($this->getDiscount(), 4) < round($itemsSubtotal, 4);
        } else {
            $this->_areAmountsValid = $itemsSubtotal > 0.00001;
        }

        return $sum;
    }


    /**
     * Import items from sales model with workarounds for Payfast
     *
     * @return void
     */
    protected function _importItemsFromSalesModel(): void
    {
        parent::_importItemsFromSalesModel();

        $this->_salesModelItems = [];

        foreach ($this->_salesModel->getAllItems() as $item) {
            if ($item->getParentItem()) {
                continue;
            }

            $amount = $item->getPrice();
            $qty    = $item->getQty();

            $subAggregatedLabel = '';

            if ($amount - round($amount, 2)) {
                $amount             = $amount * $qty;
                $subAggregatedLabel = ' x' . $qty;
                $qty                = 1;
            }

            // aggregate item price if item qty * price does not match row total
            $itemBaseRowTotal = $item->getOriginalItem()->getBaseRowTotal();
            if ($amount * $qty != $itemBaseRowTotal) {
                $amount             = (double)$itemBaseRowTotal;
                $subAggregatedLabel = ' x' . $qty;
                $qty                = 1;
            }

            $this->_salesModelItems[] = $this->_createItemFromData(
                $item->getName() . $subAggregatedLabel,
                $qty,
                $amount
            );
        }

        $this->addSubtotal($this->_salesModel->getBaseSubtotal());
        $this->addTax($this->_salesModel->getBaseTaxAmount());
        $this->addShipping($this->_salesModel->getBaseShippingAmount());
        $this->addDiscount(abs($this->_salesModel->getBaseDiscountAmount()));
    }

    /**
     * Add "hidden" discount and shipping tax
     *
     * Go ahead, try to understand ]:->
     *
     * Tax settings for getting "discount tax":
     * - Catalog Prices = Including Tax
     * - Apply Customer Tax = After Discount
     * - Apply Discount on Prices = Including Tax
     *
     * Test case for getting "hidden shipping tax":
     * - Make sure shipping is taxable (set shipping tax class)
     * - Catalog Prices = Including Tax
     * - Shipping Prices = Including Tax
     * - Apply Customer Tax = After Discount
     * - Create a cart price rule with % discount applied to the Shipping Amount
     * - run shopping cart and estimate shipping
     *
     * @param SalesModelInterface $salesEntity
     *
     * @return void
     */
    protected function _applyDiscountTaxCompensationWorkaround(
        SalesModelInterface $salesEntity
    ): void {
        $dataContainer = $salesEntity->getTaxContainer();
        $this->addTax((double)$dataContainer->getBaseDiscountTaxCompensationAmount());
        $this->addTax((double)$dataContainer->getBaseShippingDiscountTaxCompensationAmnt());
    }
}
