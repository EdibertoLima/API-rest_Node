const {ContactMacapa,ContactVarejao} = require ("../models/contact")

class addContact {
    async addContactMacapa(req,res){
        const resp=req.body; 
        await ContactMacapa.bulkCreate(resp.contacts, { individualHooks: true });
        //await ContactMacapa.sync({ force: true });
        return res.json(true);
    }
    async addContactVarejao(req,res){
        const resp=req.body; 
        await ContactVarejao.bulkCreate(resp.contacts, { individualHooks: true });
        //await ContactVarejao.sync({ force: true });
        return res.json(true);

    }
}

module.exports= addContact;