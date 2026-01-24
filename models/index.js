const { Sequelize } = require('sequelize');
const path = require('path');

// Default: SQLite local database for quick setup.
// To use Postgres/MySQL, replace the Sequelize constructor with appropriate connection string.
const sequelize = new Sequelize({
  dialect: 'sqlite',
  storage: path.join(__dirname, '..', 'database.sqlite'),
  logging: false
});

const Official = require('./official')(sequelize);

module.exports = { sequelize, Official };
