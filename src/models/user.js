const { Model, DataTypes } = require("sequelize");

class UserMacapa extends Model {
    static init(sequelize) {
        super.init({
                
        }), {
            sequelize,
            modelName: "users",
            timestamps: false
        }
    }
}