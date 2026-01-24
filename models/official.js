const { DataTypes } = require('sequelize');

module.exports = (sequelize) => {
  const Official = sequelize.define('Official', {
    id: {
      type: DataTypes.INTEGER,
      primaryKey: true,
      autoIncrement: true
    },
    firstName: {
      type: DataTypes.STRING,
      allowNull: false
    },
    middleName: {
      type: DataTypes.STRING,
      allowNull: true
    },
    lastName: {
      type: DataTypes.STRING,
      allowNull: false
    },
    position: {
      type: DataTypes.STRING,
      allowNull: false
    },
    contactNumber: {
      type: DataTypes.STRING,
      allowNull: true
    },
    email: {
      type: DataTypes.STRING,
      allowNull: true,
      validate: { isEmail: true }
    },
    termStart: {
      type: DataTypes.DATEONLY,
      allowNull: true
    },
    termEnd: {
      type: DataTypes.DATEONLY,
      allowNull: true
    },
    photoUrl: {
      type: DataTypes.STRING,
      allowNull: true
    }
  }, {
    tableName: 'officials'
  });

  return Official;
};
