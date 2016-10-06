<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\db\ActiveQuery;
use app\models\ShopProductImages;
use app\models\ShopCategory;
use app\models\ShopSiteProducts;

/**
 * This is the model class for table "shop_product".
 *
 * @property integer $id
 * @property string $id_1c
 * @property string $name
 * @property string $brand
 * @property string $summary
 * @property string $meta_title
 * @property string $meta_keywords
 * @property string $meta_description
 * @property string $description
 * @property integer $contact_id
 * @property string $create_datetime
 * @property string $edit_datetime
 * @property integer $status
 * @property integer $type_id
 * @property integer $image_id
 * @property string $image_filename
 * @property integer $sku_id
 * @property string $ext
 * @property string $url
 * @property string $rating
 * @property string $price
 * @property string $compare_price
 * @property string $currency
 * @property string $min_price
 * @property string $max_price
 * @property integer $tax_id
 * @property integer $count
 * @property integer $cross_selling
 * @property integer $upselling
 * @property integer $rating_count
 * @property string $total_sales
 * @property integer $category_id
 * @property string $badge
 * @property integer $sku_type
 * @property string $base_price_selectable
 * @property string $compare_price_selectable
 * @property string $purchase_price_selectable
 * @property integer $sku_count
 * @property integer $sort_weight
 * @property string $article
 * @property integer $multi_sku
 * @property string $new_uuid
 */
class ShopProduct extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'shop_product';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['summary', 'meta_keywords', 'meta_description', 'description'], 'string'],
            [['contact_id', 'status', 'type_id', 'image_id', 'sku_id', 'tax_id', 'count', 'cross_selling', 'upselling', 'rating_count', 'category_id', 'sku_type', 'multi_sku', 'sku_count', 'sort_weight', 'display_only_available'], 'integer'],
            [['create_datetime', 'edit_datetime'], 'safe'],
            [['rating', 'price', 'compare_price', 'min_price', 'max_price', 'total_sales', 'base_price_selectable', 'compare_price_selectable', 'purchase_price_selectable'], 'number'],
            [['id_1c', 'new_uuid'], 'string', 'max' => 36],
            [['name', 'brand', 'meta_title', 'url', 'badge', 'article'], 'string', 'max' => 255],
            [['image_filename'], 'string', 'max' => 225],
            [['ext'], 'string', 'max' => 10],
            [['currency'], 'string', 'max' => 3],
            ['currency', 'default', 'value' => 'UAH'],
            ['type_id', 'default', 'value' => 1],
            ['contact_id', 'default', 'value' => 1],
            ['tax_id', 'default', 'value' => 0],
            ['display_only_available', 'default', 'value' => 0],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'id_1c' => 'Id 1c',
            'name' => 'Name',
            'brand' => 'Brand',
            'summary' => 'Summary',
            'meta_title' => 'Meta Title',
            'meta_keywords' => 'Meta Keywords',
            'meta_description' => 'Meta Description',
            'description' => 'Description',
            'contact_id' => 'Contact ID',
            'create_datetime' => 'Create Datetime',
            'edit_datetime' => 'Edit Datetime',
            'status' => 'Status',
            'type_id' => 'Type ID',
            'image_id' => 'Image ID',
            'image_filename' => 'Image Filename',
            'sku_id' => 'Sku ID',
            'ext' => 'Ext',
            'url' => 'Url',
            'rating' => 'Rating',
            'price' => 'Price',
            'compare_price' => 'Compare Price',
            'currency' => 'Currency',
            'min_price' => 'Min Price',
            'max_price' => 'Max Price',
            'tax_id' => 'Tax ID',
            'count' => 'Count',
            'cross_selling' => 'Cross Selling',
            'upselling' => 'Upselling',
            'rating_count' => 'Rating Count',
            'total_sales' => 'Total Sales',
            'category_id' => 'Category ID',
            'badge' => 'Badge',
            'sku_type' => 'Sku Type',
            'base_price_selectable' => 'Base Price Selectable',
            'compare_price_selectable' => 'Compare Price Selectable',
            'purchase_price_selectable' => 'Purchase Price Selectable',
            'sku_count' => 'Sku Count',
            'sort_weight' => 'sort_weight',
            'article' => 'Article',
            'multi_sku' => 'multi_sku',
            'new_uuid' => 'new_uuid',
            'display_only_available' => 'display_only_available',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getShopProductImages()
    {
        return $this->hasMany(ShopProductImages::className(), ['product_id' => 'id'])->orderBy('sort asc');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getShopCategoryProducts()
    {
        return $this->hasMany(ShopCategoryProducts::className(), ['product_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getShopProductSkus()
    {
        return $this->hasMany(ShopProductSkus::className(), ['product_id' => 'id'])->orderBy('sort DESC');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getShopProductImagesCount()
    {
        return $this->hasMany(ShopProductImages::className(), ['product_id' => 'id'])->count();
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getShopSiteProducts()
    {
        return $this->hasOne(ShopSiteProducts::className(), ['product_id' => 'id']);
    }


    /**
     * @inheritdoc
     * @return ShopProductQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ShopProductQuery(get_called_class());
    }

    public function extraFields()
    {
        return ['shopProductSkus','shopProductImages'];
    }

}
