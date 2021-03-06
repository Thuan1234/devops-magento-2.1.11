<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesRule\Model\Rule\Action\Discount;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\GuestCartTotalRepositoryInterface;
use Magento\Quote\Api\GuestCouponManagementInterface;
use Magento\Quote\Api\GuestCartItemRepositoryInterface;
use Magento\Quote\Api\GuestCartManagementInterface;
use Magento\TestFramework\Helper\Bootstrap;

class CartFixedTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var GuestCartManagementInterface
     */
    private $cartManagement;

    /**
     * @var GuestCartItemRepositoryInterface
     */
    private $cartItemRepository;

    /**
     * @var GuestCouponManagementInterface
     */
    private $couponManagement;

    protected function setUp()
    {
        $this->cartManagement = Bootstrap::getObjectManager()->create(GuestCartManagementInterface::class);
        $this->couponManagement = Bootstrap::getObjectManager()->create(GuestCouponManagementInterface::class);
        $this->cartItemRepository = Bootstrap::getObjectManager()->create(GuestCartItemRepositoryInterface::class);
    }

    /**
     * Applies fixed discount amount on whole cart.
     *
     * @param array $productPrices
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/SalesRule/_files/coupon_cart_fixed_discount.php
     * @dataProvider applyFixedDiscountDataProvider
     */
    public function testApplyFixedDiscount(array $productPrices)
    {
        $expectedDiscount = '-15.00';
        $couponCode =  'CART_FIXED_DISCOUNT_15';
        $cartId = $this->cartManagement->createEmptyCart();

        foreach ($productPrices as $price) {
            $product = $this->createProduct($price);

            /** @var CartItemInterface $quoteItem */
            $quoteItem = Bootstrap::getObjectManager()->create(CartItemInterface::class);
            $quoteItem->setQuoteId($cartId);
            $quoteItem->setProduct($product);
            $quoteItem->setQty(1);
            $this->cartItemRepository->save($quoteItem);
        }

        $this->couponManagement->set($cartId, $couponCode);

        /** @var GuestCartTotalRepositoryInterface $cartTotalRepository */
        $cartTotalRepository = Bootstrap::getObjectManager()->get(GuestCartTotalRepositoryInterface::class);
        $total = $cartTotalRepository->get($cartId);

        $this->assertEquals($expectedDiscount, $total->getBaseDiscountAmount());
    }

    /**
     * @return array
     */
    public function applyFixedDiscountDataProvider()
    {
        return [
            'prices when discount had wrong value 15.01' => [[22, 14, 43, 7.50, 0.00]],
            'prices when discount had wrong value 14.99' => [[47, 33, 9.50, 42, 0.00]],
        ];
    }

    /**
     * Returns simple product with given price.
     *
     * @param float $price
     * @return \Magento\Catalog\Api\Data\ProductInterface
     */
    private function createProduct($price)
    {
        $name = 'simple-' . $price;
        $productRepository = Bootstrap::getObjectManager()->get(ProductRepository::class);
        $product = Bootstrap::getObjectManager()->create(Product::class);
        $product->setTypeId('simple')
            ->setAttributeSetId(4)
            ->setWebsiteIds([1])
            ->setName($name)
            ->setSku(uniqid($name))
            ->setPrice($price)
            ->setMetaTitle('meta title')
            ->setMetaKeyword('meta keyword')
            ->setMetaDescription('meta description')
            ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
            ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
            ->setStockData(['qty' => 1, 'is_in_stock' => 1])
            ->setWeight(1);

        return $productRepository->save($product);
    }
}
