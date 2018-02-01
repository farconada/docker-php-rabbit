<?php
/**
 * Created by PhpStorm.
 * User: fernando
 * Date: 1/2/18
 * Time: 19:40
 */

namespace App\Command;


use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProducerCommand extends Command
{
    /**
     * @var AMQPStreamConnection
     */
    protected $mq_connection;

    /**
     * @var \PhpAmqpLib\Channel\AMQPChannel
     */
    protected $mq_channel;


    protected $mq_config;

    public function __construct(?string $name = null,string $mq_server,string $mq_port,string $mq_user,string $mq_password,string $mq_queue_name)
    {
        parent::__construct($name);
        $this->mq_config['server'] = $mq_server;
        $this->mq_config['port'] = $mq_port;
        $this->mq_config['user'] = $mq_user;
        $this->mq_config['password'] = $mq_password;
        $this->mq_config['queue'] = $mq_queue_name;
    }

    protected function configure()
    {
        $this->setName('app:producer');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->mq_connection = new AMQPStreamConnection($this->mq_config['server'], $this->mq_config['port'], $this->mq_config['user'], $this->mq_config['password']);
        $this->mq_channel = $this->mq_connection->channel();
        $this->mq_channel->queue_declare($this->mq_config['queue'], false, true, false, false);

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $data = [1,2,3,4,5,6,7,8,9];
        foreach ($data as $d) {
            $msg = new AMQPMessage($d, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT] );
            $this->mq_channel->basic_publish($msg, '', 'task_queue');
            echo " [x] Sent ", $d, "\n";
            sleep(5);

        }
        $this->mq_channel->close();
        $this->mq_connection->close();
    }


}