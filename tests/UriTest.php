<?php
use \Enrise\Uri;

class Enrise_UriTest extends \PHPUnit_Framework_TestCase
{
    public function testFromString()
    {
        $uri = new Uri('http://usr:pss@example.com:81/mypath/myfile.html?a=b&b[]=2&b[]=3#myfragment');
        $this->assertSame($uri->getScheme(), 'http');
        $this->assertSame($uri->getUser(), 'usr');
        $this->assertSame($uri->getPass(), 'pss');
        $this->assertSame($uri->getHost(), 'example.com');
        $this->assertSame($uri->getPort(), 81);
        $this->assertSame($uri->getPath(), '/mypath/myfile.html');
        $this->assertSame($uri->getQuery(), 'a=b&b[]=2&b[]=3');
        $this->assertSame($uri->getFragment(), 'myfragment');
    }

    /**
     * @dataProvider provideIsSchemelessFromString
     * @param $url
     * @param $check
     */
    public function testIsSchemelessFromString($url, $check)
    {
        $uri = new Uri($url);
        $this->assertSame($check, $uri->isSchemeless());
    }

    public function provideIsSchemelessFromString()
    {
        return [
            ['http://test.com', false],
            ['/relative/path.html', true], //Relative is always schemeless
            ['//test.com/path.html', true],
            ['/test.com/path.html', true],
            ['http://test.com//path.html', false],
            ['mailto:test@hi.com', false],
            ['://weird-uri.com/m.test.html', true], //'' == no scheme
        ];
    }

    public function testIsSchemeless()
    {
        $uri = new Uri();
        $uri->setAbsolute();
        $this->assertSame(true, $uri->isSchemeless());
        $uri->setRelative();
        $this->assertSame(true, $uri->isSchemeless());
        $uri->setAbsolute();
        $this->assertSame('//', $uri->getUri());
        $uri->setScheme('http');
        $this->assertSame(false, $uri->isSchemeless());
        $this->assertSame('http://', $uri->getUri());
        $uri->setRelative();
        $this->assertSame(true, $uri->isSchemeless());
        $this->assertSame('', $uri->getUri());
    }

    /**
     * @param $set
     * @param $expected
     * @dataProvider provideSetPort
     */
    public function testSetPort($set, $expected)
    {
        $uri = new Uri();
        $uri->setPort($set);
        $this->assertSame($uri->getPort(), $expected);
    }

    public function provideSetPort()
    {
        return [
            [0, 0],
            [1234, 1234],
            ['1234', 1234],
            [031, 25],
            [5.6, null],
            ["6.5", null],
            [0x539, 1337],
            [02471, 1337],
            [0b10100111001, 1337],
            [1337e0, 1337],
            ['string', null],
            ['8888', 8888],
            [-1, null],
            [array(), null],
            [null, null],
        ];
    }

    /**
     * @param $set
     * @param $expected
     * @dataProvider provideSetQuery
     */
    public function testSetQuery($set, $expected)
    {
        $uri = new Uri();
        $uri->setQuery($set);
        $this->assertSame($uri->getQuery(), $expected);
    }

    public function provideSetQuery()
    {
        return [
            ['hi=a', 'hi=a'],
            ['hi[]=a', 'hi[]=a'],
            ['123[]=asdf&123[]=qwer', '123[]=asdf&123[]=qwer'],
            ['?a=n', 'a=n'],
            ['?a=n&b=a&%20=40', 'a=n&b=a&%20=40'],
            [-1, null],
            [1234, null],
            [array(), null],
            [array(), null],
            [null, null],
        ];
    }

    /**
     * @param $set
     * @param $expected
     * @dataProvider provideSetScheme
     */
    public function testSetScheme($set, $expected)
    {
        $uri = new Uri();
        $uri->setScheme($set);
        $this->assertSame($uri->getScheme(), $expected);
    }

    public function provideSetScheme()
    {
        return [
            [0, null],
            ['', null],
            ['http://', 'http'],
            ['ftp://', 'ftp'],
            ['FTP://', 'ftp'],
            ['FTP:', 'ftp'],
            ['scheme:child:scheme.VALIDscheme123:', 'scheme:child:scheme.validscheme123'],
            ['//test://', 'test'],
            ['chrome-extension:', 'chrome-extension'],
            ['V20.a://', 'v20.a'],
            ['://', null],
            ['//', null],
            [':/', null],
            [':', null],
        ];
    }

    public function testRelativeAbsoluteUrls()
    {
        /**
         * So, whats happening here?
         *
         * We can put in relative and absolute URLs.
         * Relative: "otherfolder/file.html"
         * Absolute: "http://site.com/otherfolder/file.html"
         *
         * So if we put in a URL, this is what we assume:
         *
         * /hi/index.html -> Relative, no host, no user, no schema, and the path = /hi/index.html
         * //hi/index.html -> Absolute, schemaless, hi = host, /index.html = path
         *
         * If we take a relative URL, like this one; example.org/hi.html and we set the schema,
         * it should turn in a an absolute URL;
         *
         * http://example.org/hi.html
         *
         * With that in mind, the first part until the slash will be turned in to the host (example.org) and everything
         * after that is the path.
         *
         * URI: example.org/hi.html
         *  Path: example.org/hi.html
         *  Host: NULL
         *  Scheme: NULL
         *  ->getUri -> "example.org/hi.html"
         *  ->setScheme('http')
         *  Path: /hi.html
         *  Host: example.org
         *  Scheme: http
         * ->getUri -> "http://example.org/hi.html"
         */
        $uri = new Uri('relative/url/test.html?q=123#fragged');
        $this->assertSame(true, $uri->isRelative());
        $this->assertSame(false, $uri->isAbsolute());
        $this->assertSame('relative/url/test.html', $uri->getPath());
        $this->assertSame(null, $uri->getHost());
        $this->assertSame(null, $uri->getUser());
        $this->assertSame(null, $uri->getPort());
        $this->assertSame(null, $uri->getPass());
        $this->assertSame('q=123', $uri->getQuery());
        $this->assertSame('fragged', $uri->getFragment());
        $this->assertSame('relative/url/test.html?q=123#fragged', $uri->getUri());
        $uri->setScheme('http');
        $this->assertSame(false, $uri->isRelative());
        $this->assertSame(true, $uri->isAbsolute());
        $this->assertSame('/url/test.html', $uri->getPath());
        $this->assertSame('relative', $uri->getHost());
        $this->assertSame('q=123', $uri->getQuery());
        $this->assertSame('fragged', $uri->getFragment());
        $this->assertSame('http://relative/url/test.html?q=123#fragged', $uri->getUri());
        //Anohter test with some port and user stuff
        $uri = new Uri('relative/url.html');
        $this->assertSame('relative/url.html', $uri->getUri());
        $uri->setUser('Testuser');
        $this->assertSame('relative/url.html', $uri->getUri());
        $this->assertSame('relative/url.html', $uri->getPath());
        $this->assertSame(null, $uri->getHost());
        $uri->setScheme('https');
        $this->assertSame('https://Testuser@relative/url.html', $uri->getUri());
        $this->assertSame('/url.html', $uri->getPath());
        $uri = new Uri('//test.host.com/path/index.html'); //Absolute
        $this->assertSame(true, $uri->isAbsolute());
        $this->assertSame('test.host.com', $uri->getHost());
        $this->assertSame('/path/index.html', $uri->getPath());
        $this->assertSame('//test.host.com/path/index.html', $uri->getUri());
        $uri = new Uri('/test.file.path/path/index.html'); //Relative
        $this->assertSame(null, $uri->getHost());
        $this->assertSame(true, $uri->isRelative());
        $this->assertSame('/test.file.path/path/index.html', $uri->getPath());
        $this->assertSame('/test.file.path/path/index.html', $uri->getUri());
        $uri = new Uri('192.168.1.56/path/index.html'); //Relative
        $this->assertSame(null, $uri->getHost());
        $this->assertSame(true, $uri->isRelative());
        $uri->setScheme('HtTpScHeaa---mmee....testscheme-isvalisd123');
        $this->assertSame('/path/index.html', $uri->getPath());
        $this->assertSame('192.168.1.56', $uri->getHost());
        $this->assertSame('httpscheaa---mmee....testscheme-isvalisd123:192.168.1.56/path/index.html', $uri->getUri());
    }

    public function testSchemesWithAndWithoutAuthority()
    {
        //With authority would append // behind the scheme, without should not.
        $uri =  new Uri('www.hi.nl');
        $uri->setScheme('HtTpS');
        $this->assertSame('https://www.hi.nl', $uri->getUri());
        $uri =  new Uri('www.hi.nl');
        $uri->setScheme('httP');
        $this->assertSame('http://www.hi.nl', $uri->getUri());
        $uri =  new Uri('www.hi.nl');
        $uri->setScheme('ftp');
        $this->assertSame('ftp://www.hi.nl', $uri->getUri());
        //Unknown, so presume no authority:
        $uri =  new Uri('www.hi.nl');
        $uri->setScheme('mailto');
        $this->assertSame('mailto:www.hi.nl', $uri->getUri());
        //Unknown, so presume no authority:
        $uri =  new Uri('blank');
        $uri->setScheme('about');
        $this->assertSame('about:blank', $uri->getUri());
    }

    /**
     * @param $uri
     * @param $scheme
     * @param $expectedUri
     * @dataProvider provideStaticSchemaChanges
     */
    public function testStaticSchemaChanger($uri, $scheme, $expectedUri)
    {
        $this->assertSame($expectedUri, Uri::changeScheme($uri, $scheme));
    }

    //This one differs a little bit from the orginal one - the static varian't doesn't change ANYTHING
    // if the "schema" var is NULL. This is for BC. The regular scheme changer sets the schema to schemaless
    // if "setSchema(null)" - the static one doesn't change anything.
    public function provideStaticSchemaChanges()
    {
        return [
            [
                'http://usr:pss@example.com:81/mypath/myfile.html?a=b&b[]=2&b[]=3#myfragment',
                'https:',
                'https://usr:pss@example.com:81/mypath/myfile.html?a=b&b[]=2&b[]=3#myfragment'
            ],
            ['http://test.url.com', 'https', 'https://test.url.com'],
            ['http://test.url.com', 'ftp', 'ftp://test.url.com'],
            ['http://test.url.com', 'https://', 'https://test.url.com'],
            ['http://test.url.com', 'https:', 'https://test.url.com'],
            ['ftp://test.url.com2', 'https', 'https://test.url.com2'],
            ['http://test.url.com', 'https', 'https://test.url.com'],
            ['//test.example.org/asdf?a=b#123', 'https', 'https://test.example.org/asdf?a=b#123'],
            ['//test.example.org/asdf?a=b#123', null, '//test.example.org/asdf?a=b#123'], //Differs here!
            ['/test.example.org/asdf?a=b#123', null, '/test.example.org/asdf?a=b#123'], //Differs here!
            ['/test.example.org/asdf?a=b#123', 'ftp', 'ftp://test.example.org/asdf?a=b#123'],
            ['test.example.org/asdf?a=b#123', 'ftp', 'ftp://test.example.org/asdf?a=b#123'],
            ['//test.example.org/asdf?a=b#123', 'ftp', 'ftp://test.example.org/asdf?a=b#123'],
            ['https://test.example.org/asdf?a=b#123', null, 'https://test.example.org/asdf?a=b#123'], //Differs here!
            ['ftp://test.example.org/asdf?a=b#123', null, 'ftp://test.example.org/asdf?a=b#123'], //Differs here!
            ['/test.example.org/asdf?a=b#123', 'http', 'http://test.example.org/asdf?a=b#123'],
            ['/test.example.org/asdf?a=b#123', 'https', 'https://test.example.org/asdf?a=b#123'],
            ['/test.example.org/asdf?a=b#123', null, '/test.example.org/asdf?a=b#123'], //Differs here!
            ['test.example.org/asdf?a=b#123', 'http://', 'http://test.example.org/asdf?a=b#123'],
            ['test.example.org/asdf?a=b#123', null, 'test.example.org/asdf?a=b#123'], //Differs here!
            ['relative/url.html', null, 'relative/url.html'], //Differs here!
            ['relative/url.html', 'http', 'http://relative/url.html'], // setting the scheme on a relative URL makes it absolute
            //Malformed / weird urls
            ['asdf', null, 'asdf'], //Differs here!
            [1234, null, 1234], //Differs here!
            [array(), null, array()], //Differs here!
            [1234, 'https', 'https://1234'],
            ['192.168.1.56', 'https', 'https://192.168.1.56'],
            ['/////', 'https', 'https://'],
            ['/////', null, '/////'], //Differs here!
            ['hi@hoi.nl', 'mailto', 'mailto:hi@hoi.nl'],
        ];
    }

    /**
     * @param $uri
     * @param $scheme
     * @param $expectedUri
     * @dataProvider provideSchemaChanges
     */
    public function testRegularSchemaChanges($uri, $scheme, $expectedUri)
    {
        $obj = new Uri($uri);
        $this->assertSame($expectedUri, $obj->setScheme($scheme)->__toString());
        $this->assertSame($expectedUri, $obj->setScheme($scheme)->getUri());
        $this->assertSame($expectedUri, '' . $obj->setScheme($scheme));
    }

    public function provideSchemaChanges()
    {
        return [
            [
                'http://usr:pss@example.com:81/mypath/myfile.html?a=b&b[]=2&b[]=3#myfragment',
                'https:',
                'https://usr:pss@example.com:81/mypath/myfile.html?a=b&b[]=2&b[]=3#myfragment'
            ],
            ['http://test.url.com', 'https', 'https://test.url.com'],
            ['http://test.url.com', 'ftp', 'ftp://test.url.com'],
            ['http://test.url.com', 'https://', 'https://test.url.com'],
            ['http://test.url.com', 'https:', 'https://test.url.com'],
            ['ftp://test.url.com2', 'https', 'https://test.url.com2'],
            ['http://test.url.com', 'https', 'https://test.url.com'],
            ['//test.example.org/asdf?a=b#123', 'https', 'https://test.example.org/asdf?a=b#123'],
            ['//test.example.org/asdf?a=b#123', null, '//test.example.org/asdf?a=b#123'],
            ['/test.example.org/asdf?a=b#123', null, '/test.example.org/asdf?a=b#123'],
            ['/test.example.org/asdf?a=b#123', 'ftp', 'ftp://test.example.org/asdf?a=b#123'],
            ['test.example.org/asdf?a=b#123', 'ftp', 'ftp://test.example.org/asdf?a=b#123'],
            ['//test.example.org/asdf?a=b#123', 'ftp', 'ftp://test.example.org/asdf?a=b#123'],
            ['https://test.example.org/asdf?a=b#123', null, '//test.example.org/asdf?a=b#123'],
            ['ftp://test.example.org/asdf?a=b#123', null, '//test.example.org/asdf?a=b#123'],
            ['/test.example.org/asdf?a=b#123', 'http', 'http://test.example.org/asdf?a=b#123'],
            ['/test.example.org/asdf?a=b#123', 'https', 'https://test.example.org/asdf?a=b#123'],
            ['/test.example.org/asdf?a=b#123', null, '/test.example.org/asdf?a=b#123'],
            ['test.example.org/asdf?a=b#123', 'http://', 'http://test.example.org/asdf?a=b#123'],
            ['test.example.org/asdf?a=b#123', null, 'test.example.org/asdf?a=b#123'],
            ['relative/url.html', null, 'relative/url.html'],
            ['relative/url.html', 'http', 'http://relative/url.html'], // setting the scheme on a relative URL makes it absolute
            //Malformed / weird urls
            ['asdf', null, 'asdf'],
            [1234, null, '1234'],
            [1234, 'https', 'https://1234'],
            ['/////', 'https', 'https://'],
            ['/////', null, '//'],
            [array(), null, ''],
            ['hi@hoi.nl', 'mailto', 'mailto:hi@hoi.nl'],
        ];
    }

    public function testCreatingUris()
    {
        $obj = new Uri();
        $obj->setScheme('https')->setHost('www.example.org')->setPath('/car/123/18/test.html')->setQuery('id=123');
        $expectedUri = 'https://www.example.org/car/123/18/test.html?id=123';
        $this->assertSame($expectedUri, '' . $obj);
        $this->assertSame($expectedUri, $obj->getUri());
        $obj = new Uri();
        $obj->setScheme('ftp')
            ->setHost('www.example.org')
            ->setFragment('fraggy')
            ->setPath('/car/123/18/test.html')
            ->setQuery('id=123&asdf=34')
            ->setUser('username');
        $expectedUri = 'ftp://username@www.example.org/car/123/18/test.html?id=123&asdf=34#fraggy';
        $this->assertSame($expectedUri, '' . $obj);
        $this->assertSame($expectedUri, $obj->getUri());
        $obj = new Uri($expectedUri);
        $obj->setPass('password');
        $expectedUri = 'ftp://username:password@www.example.org/car/123/18/test.html?id=123&asdf=34#fraggy';
        $this->assertSame($expectedUri, '' . $obj);
        $this->assertSame($expectedUri, $obj->getUri());
        $obj = new Uri($expectedUri);
        $obj->setHost('www.xn--caf-dma.eu')->setQuery('');
        $expectedUri = 'ftp://username:password@www.xn--caf-dma.eu/car/123/18/test.html#fraggy';
        $this->assertSame($expectedUri, '' . $obj);
        $this->assertSame($expectedUri, $obj->getUri());
        $obj = new Uri($expectedUri);
        $obj->setPort(8888)->setScheme('')->setFragment('');
        $expectedUri = '//username:password@www.xn--caf-dma.eu:8888/car/123/18/test.html';
        $this->assertSame($expectedUri, '' . $obj);
        $this->assertSame($expectedUri, $obj->getUri());
    }

    public function testChangeScheme()
    {
        $this->assertSame('https://website.com/index.html', Uri::changeScheme('//website.com/index.html', 'https'));
        $this->assertSame('https://website.com/index.html', Uri::changeScheme('/website.com/index.html', 'https'));
        $this->assertSame('https://website.com/index.html', Uri::changeScheme('website.com/index.html', 'https'));
        $this->assertSame('https://website.com/index.html', Uri::changeScheme('http://website.com/index.html', 'https'));
    }
}