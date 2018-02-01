<?php
/**
 * Created by PhpStorm.
 * User: fernando
 * Date: 1/2/18
 * Time: 17:06
 */

namespace App\Command;


use App\Service\UpdateDbService;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WorkerCommand extends Command
{
    /**
     * @var AMQPStreamConnection
     */
    protected $mq_connection;

    /**
     * @var \PhpAmqpLib\Channel\AMQPChannel
     */
    protected $mq_channel;

    protected $service;

    protected $mq_config;

    public function __construct(?string $name = null, UpdateDbService $service,string $mq_server,string $mq_port,string $mq_user,string $mq_password,string $mq_queue_name)
    {
        parent::__construct($name);
        $this->service = $service;
        $this->mq_config['server'] = $mq_server;
        $this->mq_config['port'] = $mq_port;
        $this->mq_config['user'] = $mq_user;
        $this->mq_config['password'] = $mq_password;
        $this->mq_config['queue'] = $mq_queue_name;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->mq_connection = new AMQPStreamConnection($this->mq_config['server'], $this->mq_config['port'], $this->mq_config['user'], $this->mq_config['password']);
        $this->mq_channel = $this->mq_connection->channel();
        $this->mq_channel->queue_declare($this->mq_config['queue'], false, true, false, false);
        $this->mq_channel->basic_qos(null, 1, null);

        $service = $this->service;
        $function_callback = function(AMQPMessage $msg) use ($service){
            var_dump($msg->body);
            // AMQP message decoupling
            $content = json_decode($msg->body, true);
            $service->execute($content);
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };

        $this->mq_channel->basic_consume('task_queue', '', false, false, false, false, $function_callback);
    }


    protected function configure()
    {
        $this->setName('app:worker');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        while(count($this->mq_channel->callbacks)) {
            $this->mq_channel->wait();
        }
        $this->mq_channel->close();
        $this->mq_connection->close();
    }


}