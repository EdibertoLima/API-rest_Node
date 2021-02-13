const { Model, DataTypes } = require("sequelize");

class ContactMacapa extends Model {
    static init(sequelize) {
        super.init({
            name: DataTypes.STRING(200),
            email: DataTypes.STRING(20),
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
            name: DataTypes.STRING(100),
            email: DataTypes.STRING(13),
        }, {
            sequelize,
            modelName: 'contacts',
            // don't forget to enable timestamps!
            timestamps: false,

        })
    }
}


module.exports = {ContactMacapa,ContactVarejao};