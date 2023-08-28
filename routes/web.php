<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/


// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
/**
 * @info Routes
 */
$router->group(['prefix' => 'api'], function () use ($router) {

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @info Protected routes
     */
    $router->group(['middleware' => 'auth:api'], function ($router) {

        // Profile related
        $router->post('/logout', 'UserController@logout');
        $router->get('/getProfile', 'UserController@getProfile');

        // Address
        $router->post('/addAddress', 'AddressBookController@addAddress');
        $router->post('/addressBook', 'AddressBookController@addressBook');
        $router->post('/editAddress', 'AddressBookController@editAddress');
        $router->post('/removeAddress', 'AddressBookController@removeAddress');
        $router->post('/defaultAddress', 'AddressBookController@defaultAddress');

        // Wallet
        $router->post('/rechargeWallet', 'WalletController@rechargeWallet');
        $router->post('/checkWalletBalance', 'WalletController@checkWalletBalance');
        
        // Category
        $router->post('/categoryList', 'ACategoryController@categoryList');
        $router->post('/searchCategoryList', 'ACategoryController@searchCategoryList');
        $router->post('/subCategoryList', 'ProductController@subCategoryList');
        
        // Notification
        $router->post('/notificationList', 'NotificationController@notificationList');
        
        // Referral
        $router->post('/referralList', 'WalletController@referralList');
        
        // Shop
        $router->post('/shopList', 'ShopController@shopList');
        $router->post('/removeFav', 'FavShopController@removeFav');
        $router->post('/addFavShop', 'FavShopController@addFavShop');


    });



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @info Public routes
     */
    $router->get('/', 'UserController@index');
    $router->post('/resendOtp', 'UsersTempController@resendOtp');
    $router->post('/loginAccount', 'UserController@loginAccount');
    $router->post('/checkLoginOtp', 'UserController@checkLoginOtp');
    $router->post('/checkRegOtp', 'UsersTempController@checkRegOtp');
    $router->post('/signupAccount', 'UsersTempController@signupAccount');
});