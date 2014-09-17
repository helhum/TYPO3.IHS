# TYPO3.IHS

Incident Handling System for TYPO3 Security Team

## Installation Instructions

* Clone this distribution
* run `composer install`
* make sure your Settings.yaml are correct
* run database migrations by executing `./flow doctrine:migrate`


## Create Dummy User

`./flow user:create --roles Administrator admin password`