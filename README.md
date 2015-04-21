# UriHelper

[![Build Status](https://travis-ci.org/Enrise/UriHelper.svg)](https://travis-ci.org/Enrise/UriHelper)

---

A simple URI helper class with implementations of the following RFC's / STD's:
- [RFC-3986](https://tools.ietf.org/html/rfc3986)
- [STD-66](http://tools.ietf.org/html/std66)

## Usage

```php
$uri = new \Enrise\Uri('http://usr:pss@example.com:81/mypath/myfile.html?a=b&b[]=2&b[]=3#myfragment');
echo $uri->getScheme(); // http
echo $uri->getUser(); // usr
echo $uri->getPass(); // pss
echo $uri->getHost(); // example.com
echo $uri->getPort(); // 81
echo $uri->getPath(); // /mypath/myfile.html
echo $uri->getQuery(); // a=b&b[]=2&b[]=3
echo $uri->getFragment(); // myfragment
echo $uri->isSchemeless(); // false
echo $uri->isRelative(); // false

$uri->setScheme('scheme:child:scheme.VALIDscheme123:');
$uri->setPort(null);

echo $uri->getUri(); // scheme:child:scheme.VALIDscheme123:usr:pss@example.com/mypath/myfile.html?a=b&b[]=2&b[]=3#myfragment
```


```php
use \Enrise\Uri;

$uri = new Uri('/relative/url.html');
echo $uri->getScheme(); // null
echo $uri->getHost(); // null
echo $uri->getPath(); // /relative/url.html
echo $uri->isSchemeless(); // true
echo $uri->isRelative(); // true
```




