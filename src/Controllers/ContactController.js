const {ContactMacapa,ContactVarejao} = require ("../models/contact")

module.exports= {
    async addContactMacapa(req,res){
        const resp=req.body; 
        await ContactMacapa.bulkCreate(resp.contacts, { individualHooks: true });
        return res.json({mensagem:"contatos adicionados"});
    },
    async addContactVarejao(req,res){
        const resp=req.body; 
        await ContactVarejao.bulkCreate(resp.contacts, { individualHooks: true });
        return res.json({mensagem:"contatos adicionados"});

    }
}

