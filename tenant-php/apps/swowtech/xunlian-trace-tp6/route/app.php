<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;


Route::get('', function (){
    header("Location:".'/admin');
    exit();
});
Route::group('qr', function (){//商品溯源码查询短地址
    Route::get('', 'common.Product/jumpH5Certify');

});
Route::group('/', function (){//商品溯源码查询短地址
    Route::get('captcha', 'index/captcha');   //验证码
});
//系统安装
Route::group('install', function () {
    Route::get('', 'install.Index/step1');
    Route::get('step2', 'install.Index/step2');
    Route::get('step3', 'install.Index/step3');
    Route::post('create_data', 'install.Index/createData');
});
Route::group('system', function (){//商品溯源码查询短地址
    Route::get('get_system_version', 'cms.System/getSystemVersion');//获取系统版本
    Route::post('system_update', 'cms.System/systemUpdate');//更新系统
    Route::group('admin', function () {
        Route::post('clear_cache', 'cms.System/clearCache');   //清除缓存
    })->middleware('CheckCms');

});

//微信授权获取token
Route::group('auth', function () {
    Route::get('wxcode_url', 'auth.Auth/wxcodeUrl');   //请求公众号code
    Route::get('get_app_type', 'auth.Auth/getAppType');   //请求公众号code
    Route::post('get_xcx_token', 'auth.Auth/getXcxToken');   //小程序获取用户token
    Route::post('get_app_token', 'auth.Auth/getAppToken');   //小程序获取用户token
    Route::post('token_verify', 'auth.Auth/verifyToken');   //验证用户token
    Route::get('gzh_token', 'auth.Auth/gzhToken');   //异步接收公众号code,获取openid，返回token
    Route::post('upinfo', 'auth.Auth/updateXcxUserInfo');   //更新用户昵称、头像
    Route::post('xcx_upinfo', 'auth.Auth/updateXcxUserInfo');   //更新用户昵称、头像

    Route::get('/get_wx_jskey', 'auth.Auth/getWxJsKey');   //公众号js调用

});

//系统配置
Route::group('sys_config', function () {
    Route::resource('sys_config_class', 'cms.SysConfigClass');   //获取配置信息分类
})->middleware('CheckCms');

//公共
Route::group('index', function () {

    Route::group('', function () {
        Route::post('/upload/img', 'cms.Common/uploadImg');   //上传图片
        Route::post('/upload/upload_video', 'cms.Common/uploadVideo');   //上传视频
        Route::post('/upload/img_id', 'cms.Common/uploadImgID');   //上传图片返还ID
        Route::post('/upload/down_img', 'cms.Common/downImg');   //下载图片
        Route::get('get_refresh', 'common.Task/getRefresh');  //定时任务
    });

});



//管理员
Route::group('admin', function () {
    Route::post('login', 'cms.Admin/login');//管理员登录
    Route::post('logout', 'cms.Admin/logout');//管理员登出

    Route::group('',function(){
        Route::get('check_login', 'cms.Common/checkLogin');//管理员检查是否登录
        Route::get('get_code', 'cms.Admin/getCode');//获取验证码
        Route::post('get_userinfo', 'cms.Admin/getUserInfo');//
        Route::post('change_password', 'cms.Admin/changePassword');//管理员修改密码
        Route::post('edit_admin', 'cms.AdminManage/editAdmin');//更新管理员
        Route::post('add_admin', 'cms.AdminManage/addAdmin');//添加管理员
        Route::get('get_all_admin', 'cms.AdminManage/getAdminAll');//获取所有管理员
        Route::post('delete_admin', 'cms.AdminManage/deleteAdmin');//删除管理员
        Route::post('ban_admin', 'cms.AdminManage/banAdmin');//删除管理员
    })->middleware('CheckCms');

    Route::any('ue_uploads', 'cms.Common/ueUploads');


    Route::group('user',function(){
    });
});
//文章、公告Article
Route::group('article', function () {
    //公共
    Route::group('', function () {
        Route::get('get_all_article', 'common.Article/getAllArticle');//获取所有的文章
        Route::get('get_article', 'common.Article/getArticle');//获取一篇公告
        Route::get('get_one_article', 'common.Article/getOneArticle');//获取文章详情
        Route::get('give_thumbs_up', 'common.Article/giveThumbsUp');//获取文章详情
        Route::get('visit', 'common.Article/visit');//获取文章详情
        Route::get('get_recent', 'common.Article/getRecent');//获取文章详情
        Route::get('user_article', 'common.Article/userArticle');//用户获取文章
        Route::get('type_article', 'common.Article/getTypeArticle');//用户获取某个类型的文章
        Route::resource('category', 'cms.ArticleCategoryManage');//文章分类
    });

    //管理员
    Route::group('admin', function () {
        Route::get('get_all_article', 'cms.ArticleManage/adminGetAllArticle');//获取所有的文章
        Route::get('all_article_name', 'cms.ArticleManage/allArticleName');//获取所有的文章
        Route::post('add_article', 'cms.ArticleManage/addArticle');//增加文章
        Route::post('edit_article', 'cms.ArticleManage/editArticle');//修改文章
        Route::put('del_article', 'cms.ArticleManage/deleteArticle');//删除文章

        Route::resource('category', 'cms.ArticleCategoryManage');//文章分类


    })->middleware('CheckCms');
});

//广告banner
Route::group('banner', function () {
    //公共
    Route::group(function () {
        Route::get('get_banner', 'common.Banner/getBannerItem');//获取某个广告
        Route::get('get_all_banner', 'common.Banner/getAllBanner');//获取所有广告位
        Route::get('banner_all_item', 'common.Banner/bannerAllItem');//获取所有广告
        Route::get('get_banner_content', 'common.Banner/getBannerContent');//获取所有广告详情
    });

    //管理员
    Route::group('admin', function () {
        Route::get('banner_list', 'cms.BannerManage/bannerList');//cms获取所有广告
        Route::post('save_banner_item', 'cms.BannerManage/saveBannerItem');//cms获取所有广告
        Route::delete('delete_banner_item', 'cms.BannerManage/deleteBannerItem');//cms获取所有广告
        Route::get('banner_item_list', 'cms.BannerManage/bannerItemList');//cms获取广告内容项目
        Route::post('add_banner', 'cms.BannerManage/addBanner');//添加广告
        Route::post('edit_banner', 'cms.BannerManage/editBanner');//修改广告
        Route::post('delete_banner', 'cms.BannerManage/deleteBanner');//删除广告
        Route::post('set_sort', 'cms.BannerManage/setSort');//更新广告排序
    })->middleware('CheckCms');
});

//分类Category
Route::group('category', function () {
    //公共
    Route::group('', function () {
        Route::get('get_category', 'common.Category/getCateLevel');//获取X级分类信息
        Route::get('all_category', 'common.Category/allCategory');//获取所有分类信息
        Route::get('category_cid', 'common.Category/getCateChildImg');//获取分类下所有子类与广告图
    });

    //管理员
    Route::group('admin', function () {
        Route::post('add_category', 'cms.CategoryManage/addCategory');//添加分类
        Route::post('edit_category', 'cms.CategoryManage/editCategory');//修改分类
        Route::put('del_category', 'cms.CategoryManage/deleteCategory');//删除分类
        Route::get('all_category', 'cms.CategoryManage/getCateSort');//cms 获取所有分类并排好序，包括隐藏
        Route::post('set_sort', 'cms.CategoryManage/setSort');//更新分类排序
    })->middleware('CheckCms');
});

//导航
Route::group('nav', function () {
    //公共
    Route::group('', function () {
        Route::get('get_nav', 'cms.NavManage/getNav');//获取X级分类信息

    });

    //管理员
    Route::group('admin', function () {
        Route::post('add_nav', 'cms.NavManage/addNav');//新增导航
        Route::post('edit_nav', 'cms.NavManage/editNav');//修改导航
        Route::put('del_nav', 'cms.NavManage/deleteNav');//删除导航
        Route::get('all_nav', 'cms.NavManage/getNav');//cms 获取所有导航
        Route::post('set_sort', 'cms.NavManage/setSort');//更新排序导航
    })->middleware('CheckCms');
});

//图片
Route::group('img_category', function () {

    //管理员
    Route::group('admin', function () {
        Route::post('add_category', 'cms.ImageManage/addImageCategory');//添加分类
        Route::post('delete_category', 'cms.ImageManage/deleteImageCategory');//删除分类
        Route::post('edit_category', 'cms.ImageManage/editImageCategory');//编辑分类
        Route::get('get_category', 'cms.ImageManage/getImageCategory');//获取所有分类
        Route::get('get_all_img', 'cms.ImageManage/getAllImage');//获取所有图片
        Route::post('edit_image', 'cms.ImageManage/editImage');//隐藏图片
        Route::post('combine_category', 'cms.ImageManage/combineCategory');//合并分类
        Route::post('/upload/img', 'cms.Common/uploadImg');   //上传图片
    })->middleware('CheckCms');
});

//分组group
Route::group('group', function () {
    //公共
    Route::group('', function () {

    });

    //管理员
    Route::group('admin', function () {
        Route::post('add_group', 'cms.Group/addGroup');//增加文章
        Route::post('edit_group', 'cms.Group/editGroup');//修改文章
        Route::put('del_group', 'cms.Group/deleteGroup');//删除文章
        Route::get('get_all_group', 'cms.Group/getAllGroup');//获取所有的分组
        Route::get('get_one_group', 'cms.Group/getOneGroup');//获取分组详情

    })->middleware('CheckCms');
});

//收藏favorite
Route::group('favorite', function () {
    Route::post('/get_one_fav', 'user.UserFavorites/scFavGood'); //查询商品是否被某用户收藏,参数type=shop为查询收藏商铺
    Route::get('/get_all_fav', 'user.UserFavorites/getFavorite');   //查询某用户收藏的所有商品与商铺
    Route::post('/add_fav', 'user.UserFavorites/addFavorite');  //添加收藏商品或店铺，fav_id,type,price,img_id}
    Route::put('/del_fav', 'user.UserFavorites/deleteFavorite');  //删除收藏，参数为id
});

//评价Rate
Route::group('rate', function () {
    //公共
    Route::group('', function () {
        Route::get('goods_rate', 'user.UserRate/getGoodsRate');//获取某个商品的所有评价

    });

    //管理员
    Route::group('admin', function () {
        Route::post('add_rate', 'cms.RateManage/addRate');//添加评价
        Route::post('add_reply', 'cms.RateManage/addReply');//回复
        Route::put('del_rate', 'cms.RateManage/deleteRate');//删除评价
        Route::get('get_all_rate', 'cms.RateManage/getAllRate');//获取所有评价
    })->middleware('CheckCms');
});

//优惠券
Route::group('coupon', function () {

    Route::group('', function () {
        Route::get('get_coupon', 'user.UserCoupon/getCoupon');//用户查看优惠券
        Route::get('add_coupon', 'user.UserCoupon/addUserCoupon');//用户领取优惠券
        Route::get('get_coupon_goods', 'common.Product/getCouponProduct');//获取优惠券能使用的商品
    });

    //用户
    Route::group('user', function () {
        Route::get('get_coupon', 'user.UserCoupon/selectUserCoupon');//用户查看自己的优惠券
        Route::post('order_coupon', 'user.UserCoupon/selectStatusCoupon');//用户查看订单可用优惠券
    });

});

//用户地址Address
Route::group('address', function () {
    Route::post('add_address', 'user.UserAddress/addAddress');//添加地址
    Route::post('edit_address', 'user.UserAddress/editAddress');//修改地址
    Route::put('del_address', 'user.UserAddress/deleteAddress');//删除地址
    Route::get('get_all_address', 'user.UserAddress/getAllAddress');//获取用户所有的地址
    Route::get('get_one_address', 'user.UserAddress/getOneAddress');//获取用户某个地址详情
    Route::get('get_default_address', 'user.UserAddress/getAddressDefault');//获取默认地址
    Route::post('set_default_address', 'user.UserAddress/setUserAddressDefault');//设置默认地址
});

//搜索
Route::group('search', function () {

    Route::group('', function () {
        Route::get('record', 'common.Search/getSearchRecord');//搜索记录

    });

    //管理员
    Route::group('admin', function () {
        Route::post('add_record', 'common.Search/addSearchGoods');//新增
        Route::put('del_record', 'common.Search/deleteSearchGoods');//删除
    })->middleware('CheckCms');
});

//订单
Route::group('order', function () {

    Route::group('', function () {
        Route::post('/create', 'common.Order/createXcxOrder'); //微信商品下单
        Route::post('/create_cart', 'common.Order/createCartOrder');//公众号下单

        Route::post('/second_pay', 'common.Pay/gzhPaySecond');   //公众号第二次支付
        Route::post('/back/:ucid', 'common.Pay/gzhPayNotify'); //公众号支付回调

        Route::post('/pay/pre_order', 'common.Pay/getPreOrder');//小程序支付
        Route::any('/pay/notify', 'common.Pay/receiveNotify');//小程序支付回调

        Route::post('get_kd', 'user.UserOrder/getCourier');//快递查询
    });

    Route::group('user', function () {
        Route::post('/all_order', 'user.UserOrder/getUserOrderAll'); //获取我的所有订单信息
        Route::post('/order_date', 'user.UserOrder/getOrderDate'); //统计订单数据
        Route::get('/get_order_one', 'user.UserOrder/getUserOrderOne'); //获取用户指定的一条订单信息
        Route::put('/del_order', 'user.UserOrder/deleteOrder'); //删除一条自己未支付的订单
        Route::post('/search', 'user.UserOrder/searchOrder'); //搜索订单
        Route::post('/set_pj', 'user.UserOrder/submitReview'); //提交评价
        Route::post('/tui_kuan', 'user.UserOrder/applyRefund'); //提交退款申请
        Route::post('/receive', 'user.UserOrder/receive'); //确认收货
    });

    //手机管理员
    Route::group('mcms', function () {
        Route::post('/check_drive', 'cms.OrderManage/checkDrive'); //未发货订单提醒
        Route::post('/get_order', 'cms.OrderManage/getOrderAll'); //CMS获取所有订单简要
        Route::post('/get_order_one', 'cms.OrderManage/getOrderOne'); //获取指定订单详细--CMS
        Route::post('/edit_courier', 'cms.OrderManage/editCourier'); //更新订单配送信息
        Route::get('/get_order_num', 'cms.Statistic/getOrderNum'); //订单统计

    })->middleware('CheckMCMS');

    //管理员
    Route::group('admin', function () {
        Route::put('/del_order', 'cms.OrderManage/deleteOrder'); //cms删除订单
        Route::post('/get_order', 'cms.OrderManage/getOrderAll'); //CMS获取所有订单简要
        Route::post('/get_order_one', 'cms.OrderManage/getOrderOne'); //获取指定订单详细--CMS
        Route::post('/edit_courier', 'cms.OrderManage/editCourier'); //更新订单配送信息
        Route::post('/edit_remark', 'cms.OrderManage/editRemark'); //添加订单备注信息
        Route::get('/get_tui_all', 'cms.OrderManage/getTuiAll'); //cms 获取所有退款信息
        Route::post('/tui_bohui', 'cms.OrderManage/rejectRefund'); //退款申请驳回
    })->middleware('CheckCms');
});

//商品
Route::group('product', function () {
    //用户
    Route::group('', function () {
        Route::get('get_product', 'common.Product/getProduct');//获取某商品详情
        Route::get('get_recent', 'common.Product/getRecent');//获取首页最新商品
        Route::get('get_recent_all', 'common.Product/getRecentAll');//获取最新商品
        Route::get('get_shop_product', 'common.Product/getShopProduct');//获取某商家所有商品
        Route::post('get_products_by_catagory', 'common.Product/getProductsByCategory');//获取某分类下所有商品
        Route::get('get_evaluate', 'common.Product/getEvaluate');//获取某个商品的所有评价
        Route::post('certify', 'common.Product/certify');//查询溯源码
        Route::get('qr_create_progress', 'common.Product/qrCreateProgress');//查询溯源码
        Route::post('get_qrcode', 'common.Product/getQrcode');//查询某商品小程序码
        Route::get('get_cate_pros', 'common.Product/getCatePros');//获取某分类下所有商品

        Route::post('download_mini_qrcode', 'common.Product/downloadMiniQrcode');//下载小程序码
        //搜索商品
        Route::get('search', 'common.Search/searchGoods');

    });

    Route::group('mcms', function () {

        Route::post('add_product', 'cms.ProductManage/addProduct');//添加商品
        Route::post('edit_product', 'cms.ProductManage/mobileEditProduct');//修改商品
        Route::put('del_product', 'cms.ProductManage/deleteProduct');//删除商品
        Route::post('all_goods_info', 'cms.ProductManage/allGoodsInfo');//获取所有商品简略信息
    })->middleware('CheckMCMS');

    //采集商品
    Route::group('copy',function (){
        Route::post('get_info', 'cms.ProductManage/getCopyProductInfo');//采集商品
    });

    //管理员
    Route::group('admin', function () {
        Route::post('add_product', 'cms.ProductManage/addProduct');//添加商品
        Route::post('edit_product', 'cms.ProductManage/editProduct');//修改商品
        Route::put('del_product', 'cms.ProductManage/deleteProduct');//删除商品
        Route::put('del_code', 'cms.ProductManage/deleteCode');//删除溯源码
        Route::put('delete_all', 'cms.ProductManage/deleteAll');//删除溯源码
        Route::put('search', 'cms.ProductManage/search');//删除商品
        Route::post('set_sort', 'cms.ProductManage/setSort');//商品排序
        Route::post('get_product_list', 'cms.ProductManage/getProductList');//获取所有上架商品，包含分页
        Route::get('get_product', 'cms.ProductManage/getProduct');//获取所有上架商品，包含分页
        Route::post('search', 'cms.ProductManage/search');//获取所有上架商品，包含分页
        Route::post('get_products_down', 'cms.ProductManage/getProductsDown');//获取所有下架商品，包含分页
        Route::post('import_code', 'cms.ProductManage/importCode');//导入溯源码
        Route::post('all_goods_info', 'cms.ProductManage/allGoodsInfo');//获取所有商品简略信息
        Route::post('get_online_product', 'cms.ProductManage/getOnlineProducts');//获取所有商品简略信息
        Route::post('get_new_product', 'cms.ProductManage/getNewProducts');//获取所有商品简略信息
        Route::post('get_hot_product', 'cms.ProductManage/getHotProducts');//获取所有商品简略信息
        Route::post('get_all_codes', 'cms.ProductManage/getAllCodes');//获取所有商品简略信息


        Route::get('all_goods_name', 'cms.ProductManage/allGoodsName');//获取所有商品ID+名称
        Route::get('get_normal_goods', 'cms.ProductManage/getNormalGoods');//获取所有未参加活动的商品
    })->middleware('CheckCms');
});

//溯源码
Route::group('code', function () {
    //用户
    Route::group('', function () {
        Route::get('get_total_certify_result', 'cms.CodeManage/getTotalCertifyResult');//商品码个人查询情况
        Route::get('get_user_writeoff_result', 'cms.CodeManage/getUserWriteoffResult');//商品码核销员核销情况
        Route::get('get_mini_qrcode', 'cms.CodeManage/getMiniQrcode');//商品码个人查询情况
    });

    //手机管理员
    Route::group('user', function () {
        Route::post('writeoff', 'cms.CodeManage/writeoff');//商品码核销员核销
        Route::post('send_goods', 'cms.CodeManage/sendGoods');//商品码核销员核销
    })->middleware('CheckMCMS');


    //管理员
    Route::group('admin', function () {

        Route::post('get_result', 'cms.CodeManage/getResult');//获得某商品二维码
        Route::post('create_qrcode', 'cms.CodeManage/createQrcode');//二维码生成
        Route::post('get_qrcode_list', 'cms.CodeManage/getQrcodeList');//二维码生成
        Route::get('delete_server_export_file', 'cms.CodeManage/deleteServerExportFile');//删除导出的溯源码xlsx下载文件
        Route::get('list_export_files', 'cms.CodeManage/listExportFiles');//导出数据文件列表
        Route::get('clear_export_dir', 'cms.CodeManage/clearExportDir');//清空导出目录
        Route::get('get_total_beteen_ids', 'cms.CodeManage/getTotalBetweenIds');//获取两个溯源码之间的溯源码个数
        Route::post('data_export', 'cms.CodeManage/dataExport');//溯源码输出

        Route::post('get_all_writeoff', 'cms.CodeManage/getAllWriteoff');//获取核销数据
        Route::post('delete_writeoff', 'cms.CodeManage/deleteWriteoff');//获取核销数据
        Route::post('get_all_code_certify_result', 'cms.CodeManage/getAllCodeCertifyResult');//获取核销数据
        Route::post('del_code_certify_result', 'cms.CodeManage/deleteCodeCertifyResult');//获取核销数据
        Route::post('code_delete', 'cms.CodeManage/codeDelete');//溯源码删除
        Route::post('code_reset', 'cms.CodeManage/codeReset');//溯源码设置
        Route::post('create_code', 'cms.CodeManage/createCode');//创建溯源码

    })->middleware('CheckCms');
});
//批次
Route::group('batch', function () {
    //用户
    Route::group('', function () {
        Route::post('get_user_sended_list', 'cms.BatchManage/getUserSentList');//获得某个商品的所有批次
        Route::get('get_batch', 'cms.BatchManage/getBatch');

    });
    //手机管理员
    Route::group('mcms', function () {

    })->middleware('CheckMCMS');
    //管理员
    Route::group('admin', function () {
        Route::post('get_batchs_by_goods', 'cms.BatchManage/getBatchesByGoods');//获得某个商品的所有批次
        Route::post('get_batchs_sended', 'cms.BatchManage/getBatchesSent');//已发货列表
        Route::post('add_batch', 'cms.BatchManage/addBatch');//
        Route::post('edit_batch', 'cms.BatchManage/editBatch');//
        Route::post('delete_batch', 'cms.BatchManage/deleteBatch');//
        Route::post('get_batch', 'cms.BatchManage/getBatch');
    })->middleware('CheckCms');
});
//粉丝
Route::group('user', function () {
    //管理员
    Route::group('admin', function () {
        Route::post('get_all_user', 'cms.UserManage/getAllUser');//获取所有用户信息
        Route::post('revert_ban', 'cms.UserManage/revertBan');//设置粉丝拉黑
        Route::post('revert_writeoff', 'cms.UserManage/revertWriteoff');//设置粉丝核销员
        Route::post('revert_sender', 'cms.UserManage/revertSender');//设置粉丝发货员
        Route::post('del_user', 'cms.UserManage/deleteUser');//
        Route::post('edit_user', 'cms.UserManage/editUser');//设置粉丝核销员
    })->middleware('CheckCms');
});

//统计
Route::group('statistic', function () {
    Route::group('', function () {

    });
    //用户
    Route::group('user', function () {

    });

    //管理员
    Route::group('admin', function () {
        Route::get('get_index_data', 'cms.Statistic/getCmsIndexData');//获取首页数据
        Route::post('get_table', 'cms.Statistic/getTableData');//获取首页图表数据
        Route::post('get_money', 'cms.Statistic/getMoneyData');//cms订单统计销售额
        Route::post('get_order', 'cms.Statistic/getOrderData');//cms统计订单数据
        Route::post('get_warning_data_province', 'cms.Statistic/getWarningDataProvince');//cms统计订单数据
        Route::post('get_warning_data_province_tootip', 'cms.Statistic/getWarningDataProvinceTooltip');//cms统计订单数据
        Route::post('get_product_verify_data', 'cms.Statistic/getProductVerifyData');//cms统计订单数据
        Route::post('get_area_verify_data', 'cms.Statistic/getAreaVerifyData');//cms统计订单数据
        Route::post('get_area_by_one_goods', 'cms.Statistic/getAreaByOneGoods');//cms统计订单数据
        Route::post('get_goods_by_area', 'cms.Statistic/getGoodsByArea');//cms统计订单数据
        Route::post('certify_area_top', 'cms.Statistic/certifyAreaTop');//地区验证码排名
        Route::post('certify_goods_top', 'cms.Statistic/certifyGoodsTop');//商品验证码排名
        Route::post('get_certify_overview', 'cms.Statistic/getCertifyOverview');//查询统计概览

        Route::post('get_writeoff_table', 'cms.Statistic/getWriteoffTable');//商品核销统计表格数据
        Route::get('get_writeoff_total', 'cms.Statistic/getWriteoffTotal');//获取首页数据

        Route::get('remind', 'cms.Statistic/remind');//获取首页图表数据
    })->middleware('CheckCms');
});

//备份
Route::group('backup', function () {
    Route::get('add_backup', 'cms.Backup/addBackup');//添加备份
    Route::put('del_backup', 'cms.Backup/deleteBackup');//添加备份
    Route::get('get_backup', 'cms.Backup/getBackup');//添加备份
})->middleware('CheckCms');;
//mcms手机管理员
Route::group('mcms', function () {

    Route::group('', function () {
        Route::put('/update', 'cms.Common/upValue');   //更新某模型下的某布尔字段,如上下级架
    });

    Route::group('admin', function () {

    });
})->middleware('CheckMCMS');

//cms管理员
Route::group('cms', function () {

    Route::group(function () {
        Route::any('/get_config_value', 'cms.System/getConfigValue');   //获取配置信息
        Route::any('/get_config', 'cms.System/getConfig');   //获取商城配置信息
        Route::post('/edit_template', 'cms.System/editTemplate');  //修改配置信息
    });

    Route::group('admin', function () {
        Route::post('set_web_auth', 'cms.AdminManage/setWebAuth');//设置前端管理员
        Route::post('/update_value', 'cms.Common/upValue');   //更新某模型下的某布尔字段,如上下级架
        Route::put('/update_value', 'cms.Common/upValue');    //兼容旧前端 PUT
        Route::post('/edit_config', 'cms.System/editConfig');  //修改配置信息
        Route::get('/auth_status', 'cms.System/getAuthStatus');  //系统授权状态
        Route::post('/auth_check', 'cms.System/checkAuth');      //检查授权
        Route::post('/auth_fetch', 'cms.System/fetchAuth');      //获取授权码
    })->middleware('CheckCms');
});
//生成批次
Route::group('create_batch', function () {

    Route::group('', function () {
    });

    Route::group('admin', function () {
        Route::post('add_create_batch', 'cms.CreateBatchManage/addCreateBatch');   //增加生成批次
        Route::post('get_batch_list', 'cms.CreateBatchManage/getBatchList');   //生成批次列表
        Route::post('edit_batch', 'cms.CreateBatchManage/editBatch');   //编辑生成批次
        Route::post('get_codes_by_create_batch_id', 'cms.CreateBatchManage/getCodesByCreateBatchId');   //根据生成批次溯源码使用情况
        Route::post('code_assign', 'cms.CreateBatchManage/codeAssign');   //根据生成批次溯源码使用情况
        Route::post('delete_batch', 'cms.CreateBatchManage/deleteCreateBatch');
        Route::get('code_assign_progress', 'cms.CreateBatchManage/codeAssignProgress');   //根据生成批次溯源码使用情况
    })->middleware('CheckCms');
});

//更新统计信息
Route::get('refresh_item', 'common.Common/refreshItem');

Route::group('admin', function () {

    Route::get('operation_progress', 'common.Common/operationProgress');

});
