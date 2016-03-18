var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.MailListWidget = Y.Base.create('mailListWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance, options){
            this.reloadMailList();
        },
        destructor: function(){
        },
        reloadMailList: function(){
            this.set('waiting', true);

            var appInstance = this.get('appInstance');
            appInstance.set('mailList', null);
            appInstance.mailList(function(err, result){
                this.set('waiting', false);
                if (!err){
                    this.renderMailList();
                }
            }, this);
        },
        renderMailList: function(){
            var mailList = this.get('appInstance').get('mailList');
            if (!mailList){
                return;
            }

            var tp = this.template,
                lst = "";

            mailList.each(function(mail){
                var attrs = mail.toJSON();
                lst += tp.replace('row', [
                    attrs, {
                        date: Brick.dateExt.convert(attrs.dateline),
                        sendErrorFlag: attrs.sendError ? tp.replace('sendErrorFlag') : '',
                    }
                ]);
            });
            tp.gel('list').innerHTML = tp.replace('list', {
                'rows': lst
            });
            this.appURLUpdate();
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget,list,row,sendErrorFlag'}
        },
        CLICKS: {
            reloadMailList: 'reloadMailList',
            mailView: {
                event: function(e){
                    var mailid = e.target.getData('id') | 0;
                    this.go('mail.view', mailid);
                }
            }
        }
    });

};