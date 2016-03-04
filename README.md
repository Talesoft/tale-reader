
# Tale Di
**A Tale Framework Component**

# What is Tale Di?

Dependency Injection.

Tale DI can automatically inject constructor arguments and `setXxx`-style setter values to instances.
The DI container will manage these dependencies and lazily wire them together.

# Installation

Install via Composer

```bash
composer require "talesoft/tale-di:*"
composer install
```

# Usage

```php

use Tale\Di\ContainerInterface;
use Tale\Di\ContainerTrait;


class App implements ContainerInterface
{
    use ContainerTrait;
}

class Config {}

class Service
{

    private $_config;

    public function __construct(Config $config)
    {

        $this->_config = $config;
    }

    public function getConfig()
    {
        return $this->_config;
    }
}

class Cache extends Service
{
}

class Renderer extends Service
{

    private $_cache;

    public function setCache(Cache $cache)
    {

        $this->_cache = $cache;
        return $this;
    }

    public function getCache()
    {
        return $this->_cache;
    }

    public function render()
    {

        return get_class($this);
    }
}


$app = new App();
$app->register(Config::class);
$app->register(Cache::class);
$app->register(AwesomeRenderer::class);

var_dump($app->get(Renderer::class)->render()); //"AwesomeRenderer", Cache and Config are auto-wired and available
    
```
