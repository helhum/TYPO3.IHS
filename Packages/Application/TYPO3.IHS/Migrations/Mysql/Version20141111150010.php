<?php
namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
	Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20141111150010 extends AbstractMigration {

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function up(Schema $schema) {
		// this up() migration is autogenerated, please modify it to your needs
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
		
		$this->addSql("CREATE TABLE typo3_ihs_domain_model_vulnerabilitytype (persistence_object_identifier VARCHAR(40) NOT NULL, value VARCHAR(255) NOT NULL, PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_issue CHANGE vulnerabilitytype vulnerabilitytype VARCHAR(40) DEFAULT NULL");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_issue ADD CONSTRAINT FK_131B49C5D079F595 FOREIGN KEY (vulnerabilitytype) REFERENCES typo3_ihs_domain_model_vulnerabilitytype (persistence_object_identifier)");
		$this->addSql("CREATE INDEX IDX_131B49C5D079F595 ON typo3_ihs_domain_model_issue (vulnerabilitytype)");
	}

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function down(Schema $schema) {
		// this down() migration is autogenerated, please modify it to your needs
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
		
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_issue DROP FOREIGN KEY FK_131B49C5D079F595");
		$this->addSql("DROP TABLE typo3_ihs_domain_model_vulnerabilitytype");
		$this->addSql("DROP INDEX IDX_131B49C5D079F595 ON typo3_ihs_domain_model_issue");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_issue CHANGE vulnerabilitytype vulnerabilitytype VARCHAR(255) NOT NULL");
	}
}