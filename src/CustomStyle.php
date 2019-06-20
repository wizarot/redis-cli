<?php
/**
 * @author will <wizarot@gmail.com>
 * @link   http://wizarot.me/
 *
 * Date: 2019/6/20
 * Time: 10:45 AM
 */

namespace Console;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Terminal;

class CustomStyle extends SymfonyStyle
{
    const MAX_LINE_LENGTH = 120;

    private $input;
    private $lineLength;
    private $bufferedOutput;
    // 定义样式颜色
    private $style;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->bufferedOutput = new BufferedOutput($output->getVerbosity(), false, clone $output->getFormatter());
        // Windows cmd wraps lines as soon as the terminal width is reached, whether there are following chars or not.
        $width = (new Terminal())->getWidth() ?: self::MAX_LINE_LENGTH;
        $this->lineLength = min($width - (int)(\DIRECTORY_SEPARATOR === '\\'), self::MAX_LINE_LENGTH);
        $style = [];
        // 导入一下显示颜色的配置文件
        include 'config/style.php';
        $this->style = $style;
        parent::__construct($input, $output);
    }


    /**
     * Formats a command comment.
     *
     * @param string|array $message
     */
    public function comment($message)
    {
        $this->block($message, null, null, "<fg={$this->style['comment']['fg']};bg={$this->style['comment']['bg']}> // </>", false, false);
    }

    /**
     * {@inheritdoc}
     */
    public function success($message)
    {
        $this->block($message, 'OK', "fg={$this->style['success']['fg']};bg={$this->style['success']['bg']}", ' ', true);
    }

    /**
     * {@inheritdoc}
     */
    public function error($message)
    {
        $this->block($message, 'ERROR', "fg={$this->style['error']['fg']};bg={$this->style['error']['bg']}", ' ', true);
    }

    /**
     * {@inheritdoc}
     */
    public function warning($message)
    {
        $this->block($message, 'WARNING', "fg={$this->style['warning']['fg']};bg={$this->style['warning']['bg']}", ' ', true);
    }

    /**
     * {@inheritdoc}
     */
    public function note($message)
    {
        $this->block($message, 'NOTE', "fg={$this->style['note']['fg']};bg={$this->style['note']['bg']}", ' ! ');
    }

    /**
     * {@inheritdoc}
     */
    public function caution($message)
    {
        $this->block($message, 'CAUTION', "fg={$this->style['caution']['fg']};bg={$this->style['caution']['bg']}", ' ! ', true);
    }


}