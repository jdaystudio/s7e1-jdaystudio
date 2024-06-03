<?php
// src/Command/RecreateAdminUserCommand.php
/**
 * Initial setting up of an Admin user, OR resetting of the admin user
 * @see /migrations/Version01.php for more info (Fixture|Seeding not required)
 *
 * Example usage
 *
 * php bin/console RecreateAdminUser newadminname
 * or
 * php bin/console RecreateAdminUser newadminname --simple
 *
 * then enter new password on request
 *
 * @author John Day jdayworkplace@gmail.com
 */

namespace App\Command;

use Doctrine\DBAL\Schema\Exception\TableDoesNotExist;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'RecreateAdminUserCommand',
    description: 'Add or reset the admin user',
)]
class RecreateAdminUserCommand extends Command
{

    public function __construct(
        private EntityManagerInterface $em,
        private ValidatorInterface $validator,
    ){
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'Admin user name')
            ->addOption('simple',InputOption::VALUE_OPTIONAL, null,'No checks on password quality')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // We don't need to check if the arguments are passed as they are flagged as required and will be requested
        // we'll use entity validation to check the values
        $username = $input->getArgument('username');
        $simple = $input->getOption('simple');

        // check for access to sqlite database file first
        $path = dirname(__FILE__).'/../../var/data.db';
        if (!is_writable($path)){
            $io->error('Unable to access database file, you may need to recreate the database and/or tables with > php bin/console doctrine:database:create; php bin/console doctrine:migrations:migrate < and then run this command again to setup the default admin user.');
            $io->info('Check its permissions possibly along the lines of <ttyuserhere:www-data>');
            return Command::FAILURE;
        }

        // If the sqlite database is missing this will attempt to recreate it, we checked for the file first
        $schemaManager = $this->em->getConnection()->createSchemaManager();

        try{
            $schemaManager->introspectTable('user');
        }catch(TableDoesNotExist $e){
            $io->error('Missing User table, you may need to recreate the tables with > php bin/console doctrine:migrations:migrate < and then run this command again to setup the default admin user.');
            return Command::FAILURE;
        }

        $plaintextPassword = $io->askHidden("Please enter a new admin password", function (string $input): string {
            if (empty($input)) {
                throw new \RuntimeException('Password cannot be empty.');
            }
        return $input;
        });

        // we could request a password confirmation, but they can just re-run this command for now
        // there should only be 1 admin user, we will remove any we find before recreating a new one
        $this->em->getRepository(User::class)->deleteAdmins();

        // create new admin user
        // (NOTE: not the public example admin, this one will get deleted after time limit unless you change the code)
        // role_user is auto added and password is auto hashed by the EventListener:UserChangedNotifier
        // when we flush the object
        $user = new User();
        $user
            ->setName($username)
            ->setPlainPassword($plaintextPassword)
            ->setRoles(['ROLE_ADMIN']);

        $groups = ['Default','password'];
        if (!$simple){
            $groups[]='strict';
        }
        $errors = $this->validator->validate($user,null,$groups);

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $io->error($error->getMessage());
            }
            return Command::FAILURE;
        }

        $this->em->persist($user);
        $this->em->flush();

        $io->success('Admin user (re)created.');
        return Command::SUCCESS;
    }
}
