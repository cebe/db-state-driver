# EDbStateDriver

This application component extends CDbConnection to be able to save
and restore current db states for testing usage.

## Requirements
* Yii 1.1.8 (untested with prior versions, might also work)
* PHP 5.3.3

## Usage

put it in place of CDbConnection in your application

Currently only works with sqlite.