# mws-laravel
Thin wrapper around Amazon's MWS SDKs.


### quick setup
- Add following to the repositories in your composer.json 
```
    {
      "type": "git",
      "url": "https://github.com/sellerlabs/mws-laravel"
    }
```
- Require `"sellerlabs/mws-laravel": "^1.0.0"`
- composer update.
- add `SellerLabs\Mws\MwsServiceProvider::class` to your service providers.
- run `php artisan vendor:publish --provider='SellerLabs\Mws\MwsServiceProvider'`
- Get add mws credentials to `config/mws.php`
