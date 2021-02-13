const {ContactMacapa,ContactVarejao} = require ("../models/contact")

class addContact {
    async addContactMacapa(req,res){
        const res=req.body; 
        await ContactMacapa.bulkCreate(res.contacts, { individualHooks: true });
    }
    async addContactVarejao(req,res){
        const res=req.body; 
        await ContactVarejao.bulkCreate(res.contacts, { individualHooks: true });
    }
}

module.exports= addContact;