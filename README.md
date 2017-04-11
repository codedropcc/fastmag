# What is it?
This is a library for fast work with magento products. At typical project with Magento 1.9.x, speed was increased in 10 times.
### Right now this library under heavy development, so keep in mind it ;)
Not recommended to use it at production.
### Can be used without magento codebase.
You can work with products without magento codebase itself, database with magento tables is enough.
### Doesn't work with magento events.
Fastmag doesn't dispatch events.
# For what?
Import/export products, manage big scope of products and so on.
# Requirements:
* Magento 1.8 or higher.
* Magento Composer Autoload

If your magento have [Magento Composer Autoload](https://github.com/romantomchak/magento-composer-autoload) installed already then you can install Fastmag via composer and it will work fine. 
Otherwise you can install [Magento Composer Autoload](https://github.com/romantomchak/magento-composer-autoload) via composer first or install Fastmag via modman. 

Modman will check have you required Autoload or not and will install it.

# Installation
### Via [composer](https://getcomposer.org):
Add to require block at your composer.json file following line:
```
"codedropcc/fastmag": "dev-master"
```
So it will at least looks like

```
{
    "require": {
        "codedropcc/fastmag": "dev-master"
    }
}
```
Below composer.json example with magento composer autoload:
```
{
    "require": {
        "codedropcc/fastmag": "dev-master",
        "magento-hackathon/magento-composer-installer": "*",
        "romantomchak/magento-composer-autoload": "*"
    },
    "extra": {
        "magento-root-dir": ".",
        "with-bootstrap-patch": false
    }
}
```

After that just run ```composer install``` or ```composer update```
That's all, Fastmag is installed and ready to work.

### Via [Modman](https://github.com/colinmollenhour/modman):
* Install modman.
* Initialize modman folder via ```modman init```.
* Run ```modman clone https://github.com/codedropcc/fastmag.git``` command.
* After that you have installed Fastmag in your system.

Sometimes OPcache or APC should be cleaned after installation.