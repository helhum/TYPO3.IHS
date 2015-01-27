# TYPO3.IHS

Incident Handling System for TYPO3 Security Team

## Installation Instructions

* Clone this distribution
* run `composer install`
* make sure your Settings.yaml are correct
* run database migrations by executing `./flow doctrine:migrate`

## Import site structure

`./flow site:import --package-key TYPO3.SecurityTypo3Org`

## Create Flow Dummy User

`./flow ihs:user:create --roles Administrator admin password`

## Create Neos Dummy User

`./flow neos:user:create`