<?php
// src/migrations/Version01.php
/**
 * Initial Database setup
 *
 * This is a basic application, with a single primary user who is the admin.
 * I'm recording the sessionID and login time to manage concurrent user access.
 * Mostly for management of the public demo but also as an exercise.
 *
 * to create a fresh database run this command at the application root folder:
 * > php bin/console doctrine:migrations:migrate
 * which should create var/data.db
 *
 * NOTE: using a sqlite file database you also need to
 * > chown console_user:web_user var/data.db   (eg chown john:www-data var/data.db)
 * > chmod 664 var/data.db
 * otherwise will get errors about ... attempting to write to a readonly database ...
 *
 * you will then need to run the admin setup command (see src/Command/RecreateAdminUserCommand.php)
 * > php bin/console RecreateAdminUser your_admin_name_here
 * and enter your_admin_password when requested
 *
 * @author John Day jdayworkplace@gmail.com
 */


declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\User;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Psr\Log\LoggerInterface;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class Version01 extends AbstractMigration
{

    public function getDescription(): string
    {
        return 'Create User Table';
    }

    /**
     * Simple seeding could be to include this line
     * $this->addSql("INSERT INTO user (name,roles) VALUES (:name,:role)", ['name'=>'admin','role'=>'["user"]']);
     * however I can't easily declare the default password with the passwordhasher here.
     * We could use the postUp() and pull in the EntityManagerInterface and UserPasswordHasherInterface services
     * but what about 'Single Responsibility'.
     * Instead, added a command (RecreateAdminUser) for seeding / resetting the admin user instead
     *
     * NOTE: the auto added remarks caused this to fail
     * --(DC2Type:json), --(DC2Type:datetime_immutable)
     *
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE user (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            name VARCHAR(60) NOT NULL,
            roles CLOB NOT NULL,
            password VARCHAR(255) DEFAULT NULL, 
            sid VARCHAR(128) DEFAULT NULL, 
            last_login_at DATETIME DEFAULT NULL,
            created DATETIME DEFAULT NULL 
            )
        ');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_NAME ON user (name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user');
    }
}
