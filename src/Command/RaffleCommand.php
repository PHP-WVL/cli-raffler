<?php

namespace PhpWvl\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Abraham\TwitterOAuth\TwitterOAuth;

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
            ->addOption('meetup-id', null, InputOption::VALUE_REQUIRED)
            ->addOption('joindin-id', null, InputOption::VALUE_REQUIRED)
            ->addOption('twitter-consumer-key', null, InputOption::VALUE_OPTIONAL)
            ->addOption('twitter-consumer-secret', null, InputOption::VALUE_OPTIONAL)
            ->addOption('twitter-oauth-token', null, InputOption::VALUE_OPTIONAL)
            ->addOption('twitter-oauth-secret', null, InputOption::VALUE_OPTIONAL)
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $list = $this->getList($input);
        $this->writeLogo($output);

        $questionHelper = new QuestionHelper();
        $itemQuestion = new Question('<question>Wuk gowe rafflen?</question>');
        $confirmQuestion = new ChoiceQuestion('<question>Moejt en?</question>', ['J' => 'joak', 'N' => 'nink']);
        $confirmQuestion->setErrorMessage('<error>Tis joak of nink en niet anders</error>');

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
     * @param InputInterface $input
     *
     * @return array
     */
    protected function getList(InputInterface $input)
    {
        $client = new \GuzzleHttp\Client();

        $comments = [];

        $result = $client->get(
            sprintf('http://api.joind.in/v2.1/events/%s/comments', $input->getOption('joindin-id'))
        );
        $eventComments = \GuzzleHttp\json_decode($result->getBody());
        foreach ($eventComments->comments as $comment) {
            $comments[] = $comment;
        }

        $result = $client->get(
            sprintf('http://api.joind.in/v2.1/events/%s/talks', $input->getOption('joindin-id'))
        );
        $talks = \GuzzleHttp\json_decode($result->getBody());

        foreach ($talks->talks as $talk) {
            $result = $client->get($talk->comments_uri);
            $talkComments = \GuzzleHttp\json_decode($result->getBody());

            foreach ($talkComments->comments as $comment) {
                $comments[] = $comment;
            }
        }

        $commentNames = array_map(
            function ($comment) {
                return $comment->user_display_name;
            },
            $comments
        );


        $result = $client->get(
            sprintf('https://api.meetup.com/php-wvl/events/%s/rsvps', $input->getOption('meetup-id'))
        );
        $rspvs = \GuzzleHttp\json_decode($result->getBody());

        $rspvs = array_filter(
            $rspvs,
            function ($rsvp) {
                return $rsvp->response === 'yes';
            }
        );

        $rsvpNames = array_map(
            function ($rsvp) {
                return $rsvp->member->name;
            },
            $rspvs
        );

        $connection = new TwitterOAuth(
            $input->getOption('twitter-consumer-key'),
            $input->getOption('twitter-consumer-secret'),
            $input->getOption('twitter-oauth-token'),
            $input->getOption('twitter-oauth-secret')
        );

        $statuses = $connection->get('search/tweets', ['q' => 'phpwvl', 'count' => 50]);
        $statuses = array_filter(
            $statuses->statuses,
            function ($status) {
                return $status->user->screen_name !== 'phpwvl';
            }
        );
        $statusNames = array_map(
            function ($status) {
                return '@' . $status->user->screen_name;
            },
            $statuses
        );

        return array_merge($rsvpNames, $commentNames, $commentNames, $statusNames);
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
