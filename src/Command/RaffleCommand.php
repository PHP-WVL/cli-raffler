<?php

namespace PhpWvl\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class RaffleCommand extends Command
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('goan')
            ->setDescription('Pick a random name from a list')
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $list = $this->getList();
        $this->writeLogo($output);

        $questionHelper = new QuestionHelper();
        $itemQuestion = new Question('<question>Wuk gowe rafflen?</question>');
        $confirmQuestion = new Question('<question>Moejt en?</question>');

        // Start a loop for each item while we have people in the list
        while ($list && $item = $questionHelper->ask($input, $output, $itemQuestion)) {
            // Run the raffler until someone wants the item
            while (true) {
                $winner = $this->pickWinner($output, $list);

                if ($questionHelper->ask($input, $output, $confirmQuestion) === 'J') {
                    $output->writeln(
                        sprintf(
                            '<bg=green>Proficiat %s meje kado "%s"</bg=green>',
                            $winner,
                            $item
                        )
                    );
                    $list = array_filter(
                        $list,
                        function ($value) use ($winner) {
                            return $value !== $winner;
                        }
                    );
                    break 1;
                } else {
                    $output->writeln(
                        sprintf(
                            '<error>%s voelt em te goed vo %s</error>',
                            $winner,
                            $item
                        )
                    );
                }
            }
        }

        $output->writeln('<info>Ti gedoan!</info>');
    }

    /**
     * Get the list of contenders
     *
     * @todo Make this dynamic
     *
     * @return array
     */
    protected function getList()
    {
        $meetup = [
            'Jachim Coudenys',
            'Tom Van Herreweghe',
            'Steven Vandeputte',
            'Ike Devolder',
            'Stijn Tilleman',
            'Stijn Blomme',
            'Vic Rau',
        ];
        $joindin = [
            'Tom Van Herreweghe',
            'Ike Devolder',
            'Stijn Tilleman',
            'Stijn Blomme',
        ];

        return array_merge($meetup, $joindin);
    }

    /**
     * @param OutputInterface $output
     */
    protected function writeLogo(OutputInterface $output)
    {
        $logo = file(__DIR__ . '/../../logo.txt', FILE_IGNORE_NEW_LINES);
        $output->writeln($logo);
    }

    /**
     * Pick a random winner from the list while adding output to the screen
     *
     * @param OutputInterface $output
     * @param array $list
     *
     * @return string
     */
    private function pickWinner(OutputInterface $output, array &$list)
    {
        $progress = new ProgressIndicator($output);

        $max = mt_rand(400, 1000);
        $winner = current($list);
        $progress->start($winner);

        for ($pointer = 0; $pointer < $max; $pointer++) {
            $this->randomSleep($progress);
            $progress->setMessage($winner);
            if (($winner = next($list)) === false) {
                $winner = reset($list);
            }
        }

        $progress->finish($winner);

        return $winner;
    }

    /**
     * Generate some random advances in the progress spinner
     *
     * @param ProgressIndicator $progress
     */
    private function randomSleep(ProgressIndicator $progress)
    {
        $random = mt_rand(40, 100);
        for ($pointer = 0; $pointer < $random; $pointer++) {
            $progress->advance();
            usleep(mt_rand(10, 50));
        }
    }
}
