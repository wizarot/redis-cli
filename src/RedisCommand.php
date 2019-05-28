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


            // 处理命令行逻辑
            switch (true) {
                case stripos($command, 'help') === 0 :
                    // 帮助列表
                    $io->title('Help List 命令列表');
                    $io->listing([
                            'help : 显示可用命令',
                            'ls : 列出所有keys',
                            'ls h?llo: 列出匹配keys,?通配1个字符,*通配任意长度字符,[aei]通配选线,特殊符号用\隔开',
                            'ttl key [ttl second] : 获取/设定生存时间,传第二个参数会设置生存时间',
                        ]
                    );
                    break;
                case stripos($command, 'ls') === 0 :
                    $parameter = trim(str_replace('ls', '', $command));
                    // 先写这里,回头再抽象
                    $this->listTable($parameter);
                    break;
                case stripos($command, 'ttl') === 0 :
                    $parameter = trim(str_replace('ttl', '', $command));
                    $parameter = explode(' ', $parameter, 2);
                    if (count($parameter) == 1) {
                        $this->getTtl($parameter);
                    } else {
                        $this->setTtl($parameter);
                    }
                    break;
                default:
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
            $data[$key] = [$type, $key];
        }

        $this->io->table(
            ['TYPE', 'KEY'],
            $data
        );


    }

    // 获取并显示数据的ttl生存时间
    protected function getTtl($parameters)
    {
        try {
            $ttl = $this->redis->ttl($parameters[0]);
            // 格式化显示
            switch ($ttl){
                case -2:
                    $ttl = '<fg=black;bg=magenta>KEY不存在</>';
                    break;
                case -1:
                    $ttl = '<fg=black;bg=cyan>永久</>';
                    break;
                default:
            }
            $this->io->table(['KEY', 'TTL (秒s)'], [
                    [$parameters[0], $ttl],
                ]
            );
        } catch (\Exception $e) {
            $this->io->error($e->getMessage());
        }

    }

}