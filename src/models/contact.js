const { Model, DataTypes } = require("sequelize");

class ContactMacapa extends Model {
    static init(sequelize) {
        super.init({
            name: {type:DataTypes.STRING(200),allowNull: false},
            cellphone:{type: DataTypes.STRING(20),allowNull: false},
        }, {
            sequelize,
            modelName: 'contacts',
            // don't forget to enable timestamps!
            timestamps: false,

        })
    }
}

class ContactVarejao extends Model {
    static init(sequelize) {
        super.init({
            name: {type:DataTypes.STRING(100),allowNull: false},
            cellphone:{type: DataTypes.STRING(13),allowNull: false},
        }, {
            sequelize,
            modelName: 'contacts',
            // don't forget to enable timestamps!
            timestamps: false,

        })
    }
}


module.exports = {ContactMacapa,ContactVarejao};