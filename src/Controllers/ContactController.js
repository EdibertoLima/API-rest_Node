const { ContactMacapa, ContactVarejao } = require("../models/contact")

module.exports = {
    async addContactMacapa(req, res) {
        const resp = req.body;
        const contacts = mask(resp);
        await ContactMacapa.bulkCreate(contacts, { individualHooks: true });
        return res.json({ mensagem: "contatos adicionados" });
    },
    async addContactVarejao(req, res) {
        const resp = req.body;
        await ContactVarejao.bulkCreate(resp.contacts, { individualHooks: true });
        return res.json({ mensagem: "contatos adicionados" });

    }
}

function mask(resp) {
    var contacts = []
    resp.contacts.forEach(function (element, i) {
        var contactsModify = {}
        contactsModify.name=element.name.toUpperCase();
        contactsModify.cellphone="+"+element.cellphone.substring(0,2)+" ("+element.cellphone.substring(2,4)+") "+element.cellphone.substring(4,8)+"-"+element.cellphone.substring(8);
        contacts[i]=contactsModify;
    });
    return contacts;
}
