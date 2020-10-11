# Fat-Free Swoole

Fat-Free needs a little help in order to make it work with Swoole. If you want another implementation of someone who went completely bananas with integrating in Swoole with Fat-Free, see [https://github.com/soyleung/Fs](https://github.com/soyleung/Fs)

In order to get this to work, clone this repo, then run `composer install` then do the following:
1. Open up base.php in the Fatfree framework. Remove the "final" keyword from `final class Base extends Prefab implements ArrayAccess {`
1. Down around line 134, change all the `private $hive, $init, $languages` etc to `protected`
1. Around line 2286, comment out the `private function __clone() {}` so the base can be cloned.

Please be aware you need to install swoole first. Also you **MUST** disable xdebug in order for swoole to run properly.

Here are some preliminary benchmarks using `ab -n 6000 -c 500 http://localhost:9501/hey`

Internal PHP Webserver:
```
Server Software:        
Server Hostname:        localhost
Server Port:            9502

Document Path:          /hey
Document Length:        44 bytes

Concurrency Level:      500
Time taken for tests:   1.909 seconds
Complete requests:      6000
Failed requests:        0
Total transferred:      2532000 bytes
HTML transferred:       264000 bytes
Requests per second:    3143.65 [#/sec] (mean)
Time per request:       159.051 [ms] (mean)
Time per request:       0.318 [ms] (mean, across all concurrent requests)
Transfer rate:          1295.53 [Kbytes/sec] received

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    5  67.7      0    1031
Processing:     6   46  50.7     38     837
Waiting:        6   46  50.7     38     837
Total:         20   51 113.2     38    1865

Percentage of the requests served within a certain time (ms)
  50%     38
  66%     39
  75%     42
  80%     43
  90%     48
  95%     96
  98%    145
  99%    151
 100%   1865 (longest request)

```

Swoole HTTP Server:
```
Server Software:        swoole-http-server
Server Hostname:        localhost
Server Port:            9501

Document Path:          /hey
Document Length:        55 bytes

Concurrency Level:      500
Time taken for tests:   0.440 seconds
Complete requests:      6000
Failed requests:        0
Total transferred:      1266000 bytes
HTML transferred:       330000 bytes
Requests per second:    13622.49 [#/sec] (mean)
Time per request:       36.704 [ms] (mean)
Time per request:       0.073 [ms] (mean, across all concurrent requests)
Transfer rate:          2806.98 [Kbytes/sec] received

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        2    7   2.2      6      18
Processing:     2    9   3.3      8      28
Waiting:        1    7   3.0      6      23
Total:          8   16   5.1     14      39

Percentage of the requests served within a certain time (ms)
  50%     14
  66%     15
  75%     15
  80%     16
  90%     18
  95%     30
  98%     34
  99%     36
 100%     39 (longest request)
```
