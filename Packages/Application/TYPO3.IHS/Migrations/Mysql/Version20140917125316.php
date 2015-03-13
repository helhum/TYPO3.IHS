<?php
namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
	Doctrine\DBAL\Schema\Schema;

/**
 * Initial creation of tables
 */
class Version20140917125316 extends AbstractMigration {

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function up(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
		
		$this->addSql("CREATE TABLE typo3_ihs_domain_model_link (persistence_object_identifier VARCHAR(40) NOT NULL, uri VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, PRIMARY KEY(persistence_object_identifier)) ENGINE = InnoDB");
		$this->addSql("CREATE TABLE typo3_ihs_domain_model_producttype (persistence_object_identifier VARCHAR(40) NOT NULL, value VARCHAR(255) NOT NULL, PRIMARY KEY(persistence_object_identifier)) ENGINE = InnoDB");
		$this->addSql("CREATE TABLE typo3_ihs_domain_model_productversion (persistence_object_identifier VARCHAR(40) NOT NULL, versionnumber INT NOT NULL, PRIMARY KEY(persistence_object_identifier)) ENGINE = InnoDB");
		$this->addSql("CREATE TABLE typo3_ihs_domain_model_advisory (identifier VARCHAR(255) NOT NULL, publishingdate DATETIME DEFAULT NULL, title VARCHAR(255) NOT NULL, abstract LONGTEXT NOT NULL, description LONGTEXT NOT NULL, PRIMARY KEY(identifier)) ENGINE = InnoDB");
		$this->addSql("CREATE TABLE typo3_ihs_domain_model_advisory_links_join (ihs_advisory VARCHAR(255) NOT NULL, ihs_link VARCHAR(40) NOT NULL, INDEX IDX_E91447E2B09C3507 (ihs_advisory), INDEX IDX_E91447E232C207E3 (ihs_link), PRIMARY KEY(ihs_advisory, ihs_link)) ENGINE = InnoDB");
		$this->addSql("CREATE TABLE typo3_ihs_domain_model_issue (persistence_object_identifier VARCHAR(40) NOT NULL, product VARCHAR(40) DEFAULT NULL, advisory VARCHAR(255) DEFAULT NULL, creationdate DATETIME NOT NULL, title VARCHAR(255) NOT NULL, abstract LONGTEXT NOT NULL, description LONGTEXT NOT NULL, vulnerabilitytype VARCHAR(255) NOT NULL, reporter VARCHAR(255) NOT NULL, state VARCHAR(255) NOT NULL, cve VARCHAR(255) NOT NULL, cvss VARCHAR(255) NOT NULL, INDEX IDX_131B49C5D34A04AD (product), INDEX IDX_131B49C54112BDD9 (advisory), PRIMARY KEY(persistence_object_identifier)) ENGINE = InnoDB");
		$this->addSql("CREATE TABLE typo3_ihs_domain_model_issue_affectedversions_join (ihs_issue VARCHAR(40) NOT NULL, ihs_productversion VARCHAR(40) NOT NULL, INDEX IDX_959E56A6E1103CE8 (ihs_issue), INDEX IDX_959E56A634712B07 (ihs_productversion), PRIMARY KEY(ihs_issue, ihs_productversion)) ENGINE = InnoDB");
		$this->addSql("CREATE TABLE typo3_ihs_domain_model_issue_links_join (ihs_issue VARCHAR(40) NOT NULL, ihs_link VARCHAR(40) NOT NULL, INDEX IDX_A72A871BE1103CE8 (ihs_issue), INDEX IDX_A72A871B32C207E3 (ihs_link), PRIMARY KEY(ihs_issue, ihs_link)) ENGINE = InnoDB");
		$this->addSql("CREATE TABLE typo3_ihs_domain_model_product (persistence_object_identifier VARCHAR(40) NOT NULL, type VARCHAR(40) DEFAULT NULL, name VARCHAR(255) NOT NULL, shortname VARCHAR(255) NOT NULL, INDEX IDX_8965B52B8CDE5729 (type), PRIMARY KEY(persistence_object_identifier)) ENGINE = InnoDB");
		$this->addSql("CREATE TABLE typo3_ihs_domain_model_product_versions_join (ihs_product VARCHAR(40) NOT NULL, ihs_productversion VARCHAR(40) NOT NULL, INDEX IDX_299BD1B68920987C (ihs_product), INDEX IDX_299BD1B634712B07 (ihs_productversion), PRIMARY KEY(ihs_product, ihs_productversion)) ENGINE = InnoDB");
		$this->addSql("CREATE TABLE typo3_ihs_domain_model_solution (persistence_object_identifier VARCHAR(40) NOT NULL, issue VARCHAR(40) DEFAULT NULL, abstract LONGTEXT NOT NULL, description LONGTEXT NOT NULL, author VARCHAR(255) NOT NULL, INDEX IDX_9BB2207F12AD233E (issue), PRIMARY KEY(persistence_object_identifier)) ENGINE = InnoDB");
		$this->addSql("CREATE TABLE typo3_ihs_domain_model_solution_fixedinversions_join (ihs_solution VARCHAR(40) NOT NULL, ihs_productversion VARCHAR(40) NOT NULL, INDEX IDX_BE2EB7DC6EBDA105 (ihs_solution), INDEX IDX_BE2EB7DC34712B07 (ihs_productversion), PRIMARY KEY(ihs_solution, ihs_productversion)) ENGINE = InnoDB");
		$this->addSql("CREATE TABLE typo3_ihs_domain_model_solution_links_join (ihs_solution VARCHAR(40) NOT NULL, ihs_link VARCHAR(40) NOT NULL, INDEX IDX_FF25247E6EBDA105 (ihs_solution), INDEX IDX_FF25247E32C207E3 (ihs_link), PRIMARY KEY(ihs_solution, ihs_link)) ENGINE = InnoDB");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_advisory_links_join ADD CONSTRAINT FK_E91447E2B09C3507 FOREIGN KEY (ihs_advisory) REFERENCES typo3_ihs_domain_model_advisory (identifier)");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_advisory_links_join ADD CONSTRAINT FK_E91447E232C207E3 FOREIGN KEY (ihs_link) REFERENCES typo3_ihs_domain_model_link (persistence_object_identifier)");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_issue ADD CONSTRAINT FK_131B49C5D34A04AD FOREIGN KEY (product) REFERENCES typo3_ihs_domain_model_product (persistence_object_identifier)");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_issue ADD CONSTRAINT FK_131B49C54112BDD9 FOREIGN KEY (advisory) REFERENCES typo3_ihs_domain_model_advisory (identifier)");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_issue_affectedversions_join ADD CONSTRAINT FK_959E56A6E1103CE8 FOREIGN KEY (ihs_issue) REFERENCES typo3_ihs_domain_model_issue (persistence_object_identifier)");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_issue_affectedversions_join ADD CONSTRAINT FK_959E56A634712B07 FOREIGN KEY (ihs_productversion) REFERENCES typo3_ihs_domain_model_productversion (persistence_object_identifier)");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_issue_links_join ADD CONSTRAINT FK_A72A871BE1103CE8 FOREIGN KEY (ihs_issue) REFERENCES typo3_ihs_domain_model_issue (persistence_object_identifier)");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_issue_links_join ADD CONSTRAINT FK_A72A871B32C207E3 FOREIGN KEY (ihs_link) REFERENCES typo3_ihs_domain_model_link (persistence_object_identifier)");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_product ADD CONSTRAINT FK_8965B52B8CDE5729 FOREIGN KEY (type) REFERENCES typo3_ihs_domain_model_producttype (persistence_object_identifier)");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_product_versions_join ADD CONSTRAINT FK_299BD1B68920987C FOREIGN KEY (ihs_product) REFERENCES typo3_ihs_domain_model_product (persistence_object_identifier)");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_product_versions_join ADD CONSTRAINT FK_299BD1B634712B07 FOREIGN KEY (ihs_productversion) REFERENCES typo3_ihs_domain_model_productversion (persistence_object_identifier)");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_solution ADD CONSTRAINT FK_9BB2207F12AD233E FOREIGN KEY (issue) REFERENCES typo3_ihs_domain_model_issue (persistence_object_identifier)");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_solution_fixedinversions_join ADD CONSTRAINT FK_BE2EB7DC6EBDA105 FOREIGN KEY (ihs_solution) REFERENCES typo3_ihs_domain_model_solution (persistence_object_identifier)");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_solution_fixedinversions_join ADD CONSTRAINT FK_BE2EB7DC34712B07 FOREIGN KEY (ihs_productversion) REFERENCES typo3_ihs_domain_model_productversion (persistence_object_identifier)");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_solution_links_join ADD CONSTRAINT FK_FF25247E6EBDA105 FOREIGN KEY (ihs_solution) REFERENCES typo3_ihs_domain_model_solution (persistence_object_identifier)");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_solution_links_join ADD CONSTRAINT FK_FF25247E32C207E3 FOREIGN KEY (ihs_link) REFERENCES typo3_ihs_domain_model_link (persistence_object_identifier)");
	}

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function down(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
		
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_advisory_links_join DROP FOREIGN KEY FK_E91447E232C207E3");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_issue_links_join DROP FOREIGN KEY FK_A72A871B32C207E3");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_solution_links_join DROP FOREIGN KEY FK_FF25247E32C207E3");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_product DROP FOREIGN KEY FK_8965B52B8CDE5729");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_issue_affectedversions_join DROP FOREIGN KEY FK_959E56A634712B07");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_product_versions_join DROP FOREIGN KEY FK_299BD1B634712B07");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_solution_fixedinversions_join DROP FOREIGN KEY FK_BE2EB7DC34712B07");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_advisory_links_join DROP FOREIGN KEY FK_E91447E2B09C3507");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_issue DROP FOREIGN KEY FK_131B49C54112BDD9");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_issue_affectedversions_join DROP FOREIGN KEY FK_959E56A6E1103CE8");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_issue_links_join DROP FOREIGN KEY FK_A72A871BE1103CE8");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_solution DROP FOREIGN KEY FK_9BB2207F12AD233E");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_issue DROP FOREIGN KEY FK_131B49C5D34A04AD");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_product_versions_join DROP FOREIGN KEY FK_299BD1B68920987C");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_solution_fixedinversions_join DROP FOREIGN KEY FK_BE2EB7DC6EBDA105");
		$this->addSql("ALTER TABLE typo3_ihs_domain_model_solution_links_join DROP FOREIGN KEY FK_FF25247E6EBDA105");
		$this->addSql("DROP TABLE typo3_ihs_domain_model_link");
		$this->addSql("DROP TABLE typo3_ihs_domain_model_producttype");
		$this->addSql("DROP TABLE typo3_ihs_domain_model_productversion");
		$this->addSql("DROP TABLE typo3_ihs_domain_model_advisory");
		$this->addSql("DROP TABLE typo3_ihs_domain_model_advisory_links_join");
		$this->addSql("DROP TABLE typo3_ihs_domain_model_issue");
		$this->addSql("DROP TABLE typo3_ihs_domain_model_issue_affectedversions_join");
		$this->addSql("DROP TABLE typo3_ihs_domain_model_issue_links_join");
		$this->addSql("DROP TABLE typo3_ihs_domain_model_product");
		$this->addSql("DROP TABLE typo3_ihs_domain_model_product_versions_join");
		$this->addSql("DROP TABLE typo3_ihs_domain_model_solution");
		$this->addSql("DROP TABLE typo3_ihs_domain_model_solution_fixedinversions_join");
		$this->addSql("DROP TABLE typo3_ihs_domain_model_solution_links_join");
	}
}