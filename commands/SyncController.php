<?php

namespace app\commands;

use yii\console\Controller;
use yii;
use app\models\ShopProduct;
use app\models\ShopCategory;
use app\models\ShopType;
use app\models\ShopCategoryProducts;
use app\models\ShopProductSkus;
use app\models\ShopProductStocks;
use app\models\ShopProductPrices;
use app\models\ShopStock;
use app\models\ShopPriceType;
use app\models\DaemonJob;

class SyncController extends Controller
{
    const DEFAULT_PRICE_ID = 1;

    public function actionProducts(array $products = array(), $daemon_pid = null, $job_id = null) {
        if (!$job_id) {
            Yii::error("Started job without job id!", __METHOD__);
            return 1;
        }
        if (!$daemon_pid) {
            Yii::error("Started job without daemon pid!", __METHOD__);
            return 1;
        }
        $job = DaemonJob::findOne(['id'=>$job_id]);
        if ($job->start($daemon_pid)) {
            // Yii::info("start ".date("Y-m-d H:i:s"), __METHOD__);
        } else {
            Yii::error("Can`t start job!", __METHOD__);
            return 1;
        }
        $c_products = count($products);

        if ($c_products == 0) {
            Yii::error("Empty array given!", __METHOD__);
            $job->failed("Empty array given!");
            return 1;
        }
        
        try {
            $result = Yii::$app->web1c->GetProductsInfo([
                'APIKey'=>Yii::app()->params['1cApiKey'],
                'ArrayProducts'=>$products,
            ]);
        } catch (Exception $e) {
            Yii::error($row->error, __METHOD__);
            Yii::error($e, __METHOD__);
            $job->failed($row->error);
            return 1;
        }
        if ($result->return->success and isset($result->return->ProductResultRow) and count($result->return->ProductResultRow) > 0) {
            $items = [];
            if (is_object($result->return->ProductResultRow)) {
                $items[] = $result->return->ProductResultRow;
            } elseif (is_array($result->return->ProductResultRow)) {
                $items = $result->return->ProductResultRow;
            }
            foreach ($items as $row) {
                try {
                if ($row->success) {
                
                    $new_product = false;
                    if (!$product = ShopProduct::findOne(['id_1c'=>$row->data->uuid])) {
                        $product = new ShopProduct();
                        $new_product = true;
                    }
                    if ($new_product) {
                        Yii::info("Создан новый товар '".$row->data->name."' артикул: ".$row->data->article, __METHOD__);
                        $product->id_1c = $row->data->uuid;
                        $product->status = 0;
                    }
                    $product->name = $row->data->name;
                    $product->article = $row->data->article;
                    $product->brand = $row->data->brand;
                    $product->multi_sku = ($row->data->multi_sku) ? 1 : 0 ;
                    $product->tax_id = 0;
                    if ($row->data->deleted) {
                        if (!$new_product and $product->status == 1) {
                            Yii::info("Товар '".$row->data->name."' помечен на удаление", __METHOD__);
                        }
                        $product->status = 0;
                    }
                    $product->sku_count = count($row->data->skus->ProductDataScu);

                    if ($new_product) {
                        if (!$product->save()) {
                            Yii::warning("Ошибка сохранения товара '".$row->data->name."'", __METHOD__);
                        }
                        unset($product);
                        $product = ShopProduct::findOne(['id_1c'=>$row->data->uuid]);
                    }
                    if (isset($row->data->new_uuid) and empty($product->new_uuid) and !empty($row->data)) {
                        $redirect_product = ShopProduct::findOne(['id_1c'=>$row->data->new_uuid]);
                        $product->new_uuid = $redirect_product->id_1c;
                    }

                    $product->type_id = $this->getTypeId($row->data->category);

                    $skus_data = array();
                    if ($product->sku_count > 1) {
                        $skus_data = $row->data->skus->ProductDataScu;
                    } else {
                        $skus_data[] = $row->data->skus->ProductDataScu;
                    }
                    foreach ($skus_data as $sku_row) {
                        $new_sku = false;
                        if (!$sku = ShopProductSkus::findOne(['id_1c'=>$sku_row->uuid])) {
                            $sku = new ShopProductSkus();
                            $new_sku = true;
                            if (!empty($sku_row->name)) {
                                Yii::info("Создана новая характеристика '".$sku_row->name."' к товару ".$row->data->name, __METHOD__);
                            }
                        }
                        $sku->name = $sku_row->name;
                        $sku->id_1c = $sku_row->uuid;
                        $sku->product_id = $product->id;
                        $sku->dublicate = 0;

                        if ($new_sku) {
                            if (!$sku->save()) {
                                Yii::warning("Ошибка сохранения характеристики ".$sku_row->name." в товаре '".$row->data->name."'", __METHOD__);
                            }
                            unset($sku);
                            $sku = ShopProductSkus::findOne(['id_1c'=>$sku_row->uuid]);
                            $sku->displayed = 0;
                        }

                        // штрихкода
                        if (isset($sku_row->barcodes) and isset($sku_row->barcodes->ProductDataBarcode)) {
                            if (count($sku_row->barcodes->ProductDataBarcode) > 1) {
                                foreach ($sku_row->barcodes->ProductDataBarcode as $barcode_row) {
                                    if ($barcode_row->inner) {
                                        $sku->sku = $barcode_row->barcode;
                                    }
                                }
                            } else {
                                $sku->sku = $sku_row->barcodes->ProductDataBarcode->barcode;
                            }
                        }

                        // цены
                        if (isset($sku_row->prices) and isset($sku_row->prices->ProductDataPrice)) {
                            $prices = [];
                            if (is_object($sku_row->prices->ProductDataPrice)) {
                                $prices[] = $sku_row->prices->ProductDataPrice;
                            } else {
                                $prices = $sku_row->prices->ProductDataPrice;
                            }
                            foreach ($prices as $price_row) {
                                $price = ShopProductPrices::find()->joinWith('price_type')->where([
                                    'sku_id'=>$sku->id, 
                                    'product_id'=>$product->id, 
                                    'shop_price_type.uuid2' => $price_row->priceType,
                                ])->one();
                                if ($price) {
                                    $price->price = $price_row->price;
                                } else {
                                    $price_type = ShopPriceType::findOne(['uuid2'=>$price_row->priceType]);
                                    if ($price_type) {
                                        $price_type_id = $price_type->id;
                                    } else {
                                        $price_type = new ShopPriceType();
                                        $price_type->uuid = $price_row->priceType;
                                        $price_type->uuid2 = $price_row->priceType;
                                        $price_type->name = $price_row->priceName;
                                        $price_type->currency = 'грн';
                                        if (!$price_type->save()) {
                                            Yii::warning("Ошибка сохранения типа цен ".$price_row->priceName." в товаре '".$row->data->name."'", __METHOD__);
                                        } else {
                                            $pt = ShopPriceType::findOne(['uuid2'=>$price_row->priceType]);
                                            $price_type_id = $pt->id;
                                        }
                                    }
                                    $price = new ShopProductPrices();
                                    $price->sku_id = $sku->id;
                                    $price->price_id = $price_type_id;
                                    $price->product_id = $product->id;
                                    $price->price = $price_row->price;
                                }
                                if (!$price->save()) {
                                    Yii::warning("Ошибка сохранения цен ".$sku_row->name." в товаре '".$row->data->name."'", __METHOD__);
                                }
                                if ($price->price_id == self::DEFAULT_PRICE_ID) {
                                    $sku->price = $price_row->price;
                                    $sku->primary_price = $price_row->price;
                                    $product->price = $price_row->price;
                                }
                            }
                        }
                        
                        // остатки        
                        $c = 0;
                        foreach ($sku_row->stocks->ProductDataStock as $stock_row) {
                            $stock = ShopProductStocks::find()->joinWith('stock')->where([
                                'sku_id'=>$sku->id, 
                                'product_id'=>$product->id, 
                                'shop_stock.uuid' => $stock_row->stockUuid
                            ])->one();
                            if ($stock) {
                                $stock->count = $stock_row->quantity;
                            } else {
                                $s = ShopStock::findOne(['uuid'=>$stock_row->stockUuid]);
                                $stock = new ShopProductStocks();
                                $stock->sku_id = $sku->id;
                                $stock->stock_id = $s->id;
                                $stock->product_id = $product->id;
                                $stock->count = $stock_row->quantity;
                            }
                            if (!$stock->save()) {
                                Yii::warning("Ошибка сохранения остатков ".$sku_row->name." в товаре '".$row->data->name."'", __METHOD__);
                            }
                            $c += $stock_row->quantity;
                        }

                        $under_the_order = ShopProductStocks::find()->where(['sku_id'=>$sku->id, 'product_id'=>$product->id, 'stock_id' => 14])->one();
                        if ($under_the_order) {
                            $c += $under_the_order->count;
                        } else {
                            $under_the_order = new ShopProductStocks();
                            $under_the_order->sku_id = $sku->id;
                            $under_the_order->stock_id = 14;
                            $under_the_order->product_id = $product->id;
                            $under_the_order->count = 0;
                            if (!$under_the_order->save()) {
                                Yii::warning("Ошибка сохранения остатков на складе ПОД ЗАКАЗ ".$sku_row->name." в товаре '".$row->data->name."'", __METHOD__);
                            }
                        }
                        
                        if (!is_integer($c)) {
                            Yii::warning("остатки не число ".$sku_row->name." в товаре '".$row->data->name."'", __METHOD__);
                        }
                        $sku->count = $c;
                        if ($sku_row->deleted) {
                            if (!$new_sku and $sku->displayed == 1 and $product->multi_sku == 1) {
                                Yii::info("Характеристика '".$sku_row->name."' к товару '".$row->data->name."' помечена на удаление", __METHOD__);
                            }
                            $sku->displayed = 0;
                        }
                        if (!$sku->save()) {
                            Yii::warning("Ошибка сохранения характеристики ".$sku_row->name." в товаре '".$row->data->name."'", __METHOD__);
                        }
                        unset($sku);
                    }
                    if (!$product->save()) {
                        Yii::warning("Ошибка сохранения товара '".$row->data->name."'", __METHOD__);
                    }
                    $this->countStocksProduct($product);
                    unset($product);

                } else {
                    if (isset($row->error)) {
                        Yii::warning($row->error, __METHOD__);
                    } else {
                        Yii::warning($row, __METHOD__);
                    }
                } //end if ($row->success)

                } catch (Exception $p_e) {
                    Yii::error($p_e, __METHOD__);
                    $job->failed($p_e);
                    return 1;
                }
            } //end foreach ($result->return->ProductResultRow as $row)
        } //end if ($result->return->success)
        $job->finish('Synchronized '.$c_products.' products.');
        // Yii::info("end ".date("Y-m-d H:i:s"), __METHOD__);  
        return 0;
    }

    public function actionAll() {
        foreach (ShopProduct::find()->select('id_1c')->asArray()->batch(100) as $p) {
    
            foreach ($p as $row) {
                foreach ($row as $c => $value) {
                    $d[] = $value;
                    # code...
                }
            }
            $jobModel = new DaemonJob();
            $jobModel->task = 'sync/products';
            $jobModel->arguments = implode(',', $d);
            if (!$jobModel->save()) {
                // fpdump("2");
            }

        } 
        return 0;
    }

    public function countStocksProduct($product)
    {
        $model = ShopProduct::findOne(['id'=>$product->id]);
        $row = (new \yii\db\Query())->select(['sum(`count`) as c'])->from('shop_product_stocks') ->where(['product_id' => $product->id])->one();
        $model->count = (int)$row['c'];
        if (!$model->save()) {
            Yii::warning("Ошибка при сохранении кол-ва в товаре ".$product->name, __METHOD__);
        }
    }

    public function getTypeId($row){
        if (!$type = ShopType::findOne(['id_1c'=>$row->uuid])) {
            $type = new ShopType();
            $type->id_1c = $row->uuid;
            $type->name = $row->name;
            $type->icon = "ss pt box";
            if ($type->save()) {
                Yii::info("Создан тип товара ".$row->name, __METHOD__);
                $type = ShopType::findOne(['id_1c'=>$row->uuid]);
            } else {
                Yii::warning("Ошибка при создании типа товара ".$row->name, __METHOD__);
            }
        } else {
            if ($type->name !== $row->name) {
                $type->name = $row->name;
                if (!$type->save()) {
                    Yii::warning("Ошибка при изменении типа товара ".$row->name, __METHOD__);
                }
            }

        }
        return $type->id;
    }
}
