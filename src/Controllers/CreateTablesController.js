const { ContactMacapa, ContactVarejao } = require("../models/contact")


module.exports = {
    async createTableMacapa(req,res,next) {
        await ContactMacapa.sync();
        next();
    },
    async createTableVarejao(req,res,next) {
        await ContactVarejao.sync();
        next();
    }

};