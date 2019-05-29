<?php

namespace Console;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


class RedisCommand extends SymfonyCommand
{
    /** @var \Redis */
    protected $redis;

    /** @var SymfonyStyle */
    protected $io;

    public function configure()
    {
        $this->setName('redis-cli')
            ->setDescription('<info>PHP的redis命令行客户端</info>');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $this->io = $io;

        // 输入服务器和port
        $host = $io->ask('Redis服务器host', '127.0.0.1');
        $port = $io->ask('Redis服务器port', '6379');
        // TODO: 输入密码..

        // 连接服务器
        try {
            $this->redis = new \Redis();
            $this->redis->connect($host, $port);
            $io->success("连接服务器 {$host}:{$port} 成功!");
        } catch (\Exception $e) {
            $io->error("连接服务器 {$host}:{$port} 失败!");
            die;
        }

        do {
            $command = trim($io->ask("{$host}:{$port}", '-'));
            // 每次执行前,查看重连数据库
            try {
                $this->redis->ping();
            } catch (\Exception $exception) {
                $this->redis->connect($host, $port);
            }
            //            $io->progressAdvance(1);


            // 处理命令行逻辑
            switch (true) {
                case stripos($command, 'ls') === 0 :
                    $parameter = trim(substr($command, 2));
                    // 先写这里,回头再抽象
                    $this->listTable($parameter);
                    break;
                case stripos($command, 'ttl') === 0 :
                    $parameter = trim(substr($command, 3));
                    $parameter = explode(' ', $parameter, 2);
                    if (count($parameter) == 1) {
                        $this->getTtl($parameter);
                    } else {
                        $this->setTtl($parameter);
                    }
                    break;
                case stripos($command, 'mv') === 0 :
                    $parameter = trim(substr($command, 2));
                    $parameter = explode(' ', $parameter, 2);
                    $this->rename($parameter);
                    break;
                case stripos($command, 'rm') === 0 :
                    $parameter = trim(substr($command, 2));
                    $parameter = explode(' ', $parameter, 2);
                    $this->rm($parameter);
                    break;
                case stripos($command, 'set') === 0 :
                    $parameter = trim(substr($command, 3));
                    $parameter = explode(' ', $parameter, 2);
                    $this->set($parameter);
                    break;
                case stripos($command, 'get') === 0 :
                    $parameter = trim(substr($command, 3));
                    $parameter = explode(' ', $parameter, 2);
                    $this->get($parameter);
                    break;
                case stripos($command, 'exit') === 0 :
                    // 退出
                    $io->success('Bye!');

                    return true;
                    break;
                case stripos($command, 'help') === 0 :
                default:
                    // 帮助列表
                    $io->title('Help List 命令列表');
                    $io->listing([
                            'help : 显示可用命令',
                            'ls : 列出所有keys',
                            'ls h?llo : 列出匹配keys,?通配1个字符,*通配任意长度字符,[aei]通配选线,特殊符号用\隔开',
                            'ttl key [ttl second] : 获取/设定生存时间,传第二个参数会设置生存时间',
                            'mv key new_key : key改名,如果新名字存在则会报错',
                            'rm key : 刪除key,暂时不支持通配符匹配(太危险,没想好是否要支持)',
                            'get key : 获取值',
                            'set key : 设置值',
                        ]
                    );

                    break;

            }

        } while (strtolower($command) != 'exit');

        $io->success('Bye!');
        die;

        $io->success('Lorem ipsum dolor sit amet');
        $io->warning('Lorem ipsum dolor sit amet');
        $io->error('Lorem ipsum dolor sit amet');


        $io->title('Lorem Ipsum Dolor Sit Amet');
        $io->section('Adding a User');
        $io->text('Lorem ipsum dolor sit amet');
        $io->listing([
                'Element #1 Lorem ipsum dolor sit amet',
                'Element #2 Lorem ipsum dolor sit amet',
                'Element #3 Lorem ipsum dolor sit amet',
            ]
        );
        $io->table(
            ['Header 1', 'Header 2'],
            [
                ['Cell 1-1', 'Cell 1-2'],
                ['Cell 2-1', 'Cell 2-2'],
                ['Cell 3-1', 'Cell 3-2'],
            ]
        );
        $io->note('Lorem ipsum dolor sit amet');
        $io->note([
                'Lorem ipsum dolor sit amet',
                'Consectetur adipiscing elit',
                'Aenean sit amet arcu vitae sem faucibus porta',
            ]
        );
        $io->caution([
                'Lorem ipsum dolor sit amet',
                'Consectetur adipiscing elit',
                'Aenean sit amet arcu vitae sem faucibus porta',
            ]
        );
        $io->progressStart();
        $io->progressStart(100);

        $io->progressAdvance();
        $io->progressAdvance(10);
        $io->progressFinish();

        $io->ask('What is your name?');
        $io->ask('Where are you from?', 'United States');//默认值
        $io->ask('Number of workers to start', 1, function($number) {
            if (!is_numeric($number)) {
                throw new \RuntimeException('You must type a number.');
            }

            return (int)$number;
        }
        );
        $io->askHidden('What is your password?');
        $password = $io->askHidden('What is your password?', function($password) {
            if (empty($password)) {
                throw new \RuntimeException('Password cannot be empty.');
            }

            return $password;
        }
        );
        var_dump($password);
        $io->confirm('Restart the web server?');
        $io->choice('Select the queue to analyze', ['queue1', 'queue2', 'queue3']);


        //        $output->writeln([
        //            '<info>Lorem Ipsum Dolor Sit Amet</info>',
        //            '<info>==========================</info>',
        //            '',
        //        ]);
    }

    // 获取列表和key对应的类型,并返回表格
    protected function listTable($search = '')
    {
        if (empty($search)) {
            $search = '*';
        }
        $keys = $this->redis->keys($search);
        sort($keys);
        $data = [];
        foreach ($keys as $key) {
            $type = $this->redis->type($key);
            if ($type == 0) {
                continue;//不存在那么就直接跳过
            }
            // 根据类型显示颜色
            //        none(key不存在) int(0)
            //        string(字符串) int(1)
            //        list(列表) int(3)
            //        set(集合) int(2)
            //        zset(有序集) int(4)
            //        hash(哈希表) int(5)
            $type = $this->transType($type);
            $data[$key] = [$type, $key];
        }

        $this->io->table(
            ['TYPE', 'KEY'],
            $data
        );


    }

    // 从 0,1,2..这种类型,转换成显示类型
    protected function transType($type)
    {
        switch ($type) {
            case 1:
                $type = '<fg=black;bg=cyan>STRING</>';
                break;
            case 2:
                $type = '<fg=black;bg=green>SET   </>';
                break;
            case 3:
                $type = '<fg=black;bg=yellow>LIST  </>';
                break;
            case 4:
                $type = '<fg=black;bg=blue>ZSET  </>';
                break;
            case 5:
                $type = '<fg=black;bg=magenta>HASH  </>';
                break;
        }

        return $type;
    }

    // 转换为方便输出的ttl格式
    protected function transTtl($ttl)
    {
        switch ($ttl) {
            case -2:
                $ttl = '<fg=black;bg=magenta>KEY不存在</>';
                break;
            case -1:
                $ttl = '<fg=black;bg=cyan>永久</>';
                break;
            default:
        }

        return $ttl;
    }

    // 尝试将string类型数据,转换为数组或反序列化,如果成功那么就返回正常显示
    protected function convertString($string)
    {
        $data = json_decode($string, true);
        if (is_array($data)) {
            return $data;
        }

        $data = @unserialize($string);
        if ($data !== false) {
            return $data;
        }

        return false;
    }

    // 获取并显示数据的ttl生存时间
    protected function getTtl($parameters)
    {
        try {
            $ttl = $this->redis->ttl($parameters[0]);
            // 格式化显示
            $ttl = $this->transTtl($ttl);
            $this->io->table(['KEY', 'TTL (秒s)'], [
                    [$parameters[0], $ttl],
                ]
            );
        } catch (\Exception $e) {
            $this->io->error($e->getMessage());
        }

    }

    // 设置过期时间
    protected function setTtl($parameters)
    {
        try {
            $result = $this->redis->EXPIRE($parameters[0], (integer)$parameters[1]);
            // 格式化显示
            $result = $result == 1 ? ('<info>生存时间设置为: ' . (integer)$parameters[1] . ' (秒)</info>') : '<error>失败</error>';
            $this->io->table(['KEY', 'TTL (秒s)'], [
                    [$parameters[0], $result],
                ]
            );
        } catch (\Exception $e) {
            $this->io->error($e->getMessage());
        }

    }

    // 重命名key
    protected function rename($parameters)
    {
        try {
            if (!key_exists('1', $parameters)) {
                throw new \Exception("缺少第2个参数");
            }
            $result = $this->redis->renameNx($parameters[0], $parameters[1]);
            // 格式化显示
            if ($result == 1) {
                // 成功
                $this->io->success("修改成功");
            } else {
                // 失败
                $this->io->error("修改失败");
            }
        } catch (\Exception $e) {
            $this->io->error($e->getMessage());
        }
    }

    // 删除key
    protected function rm($parameters)
    {
        try {
            if (!$this->redis->exists($parameters[0])) {
                throw new \Exception("KEY: {$parameters[0]} 不存在");
            }
            $confirm = $this->io->confirm("确定要删除 {$parameters[0]} ?", false);
            if ($confirm) {
                $this->redis->del($parameters[0]);
                $this->io->success("删除成功");
            }

        } catch (\Exception $e) {
            $this->io->error($e->getMessage());
        }
    }

    // 设置key
    protected function set($parameters)
    {
        try {
            $key = $parameters[0];
            if ($this->redis->exists($key)) {
                $confirm = $this->io->confirm("KEY: {$key} 已存在,确定覆盖?", false);
                if (!$confirm) {
                    return true;
                }
            }
            $type = $this->io->choice('请选择数据类型', ['<fg=black;bg=cyan>String</>', '<fg=black;bg=magenta>Hash</>', '<fg=black;bg=yellow>List</>', '<fg=black;bg=green>Set</>', '<fg=black;bg=blue>ZSet</>']);
            $type = strip_tags($type);
            // TODO: 处理不同类型数据
            switch ($type) {
                case 'String':
                    $value = $this->io->ask('请输入值', null, function($value) {
                        if (empty($value)) {
                            throw new \RuntimeException('不能为空');
                        }

                        return $value;
                    }
                    );
                    $this->redis->set($key, $value);
                    $this->io->success("Key:[$key]设置为: $value");
                    break;
            }

        } catch (\Exception $e) {
            $this->io->error($e->getMessage());
        }
    }

    // 获取key数据详细内容
    protected function get($parameters)
    {
        try {
            if (!$this->redis->exists($parameters[0])) {
                throw new \Exception("KEY: {$parameters[0]} 不存在");
            }
            $key = $parameters[0];
            // 获取类型
            $type = $this->redis->type($key);
            $typeStr = $this->transType($type);
            // 获取ttl
            $ttl = $this->redis->ttl($key);
            $ttlStr = $this->transTtl($ttl);
            // 输出
            $this->io->section('STATUS:');
            $this->io->table(
                ['TYPE', 'KEY', 'TTL'],
                [
                    [$typeStr, $key, $ttlStr],
                ]
            );

            // 根据类型显示值
            // none(key不存在) int(0)
            // string(字符串) int(1)
            // list(列表) int(3)
            // set(集合) int(2)
            // zset(有序集) int(4)
            // hash(哈希表) int(5)
            switch ($type) {
                case 0:
                    throw new \Exception("KEY: {$key} 不存在");
                    break;
                case 1:
                    $this->io->section('VALUE:');
                    $content = (string)$this->redis->get($key);
                    $this->io->text($content);
                    // 尝试转换json或者反序列化,如果成功那么就再显示下.
                    $data = $this->convertString($content);
                    if ($data !== false) {
                        $this->io->section('CONVERSION:');
                        print_r($data);
                    }
                    break;
                case 2:
                    // 集合set
                    $this->io->section('VALUE:');
                    $value = $this->redis->sMembers($key);
                    print_r($value);
                    break;
                case 3:
                    // 列表List
                    $this->io->section('VALUE:');
                    $value = $this->redis->lRange($key, 0, -1);
                    print_r($value);
                    break;
                case 4:
                    // 有续集Zset
                    $this->io->section('VALUE:');
                    $value = $this->redis->zRange($key, 0, -1);
                    $data = [];
                    foreach ($value as $id => $item) {
                        $data[] = [
                            $id,
                            $this->redis->zScore($key, $item),
                            $item,
                        ];
                    }
                    $this->io->table(
                        ['ID', 'SCORE', 'MEMBER'],
                        $data
                    );
                    break;
                case 5:
                    // 哈希表
                    $this->io->section('VALUE:');
                    $value = (array)$this->redis->hGetAll($key);
                    $data = [];
                    foreach ($value as $key => $item) {
                        $data[] = [
                            $key, $item,
                        ];
                    }
                    $this->io->table(
                        ['KEY', 'MEMBER'],
                        $data
                    );
                    break;

            }

        } catch (\Exception $e) {
            $this->io->error($e->getMessage());
        }
    }

}