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

    NS.MailViewWidget = Y.Base.create('mailViewWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance, options){
            this.set('waiting', true);

            appInstance.mail(this.get('mailid'), function(err, result){
                this.set('waiting', false);
                if (!err){
                    this.set('mail', result.mail);
                }
                this._renderMail();
            }, this);
        },
        destructor: function(){
        },
        _renderMail: function(){
            var tp = this.template,
                mail = this.get('mail');

            if (!mail){
                return;
            }

            var attrs = mail.toJSON();
            tp.setHTML(attrs);
            tp.setValue(attrs);
            tp.setValue({
                sendDate: Brick.dateExt.convert(attrs.sendDate)
            });
            tp.toggleView(attrs.sendError, 'sendErrorViewPanel');
        },
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
            mailid: {value: 0},
            mail: {value: null}
        },
        CLICKS: {
            reloadMailList: 'reloadMailList',
            mailView: {
                event: function(e){
                    var mailid = e.target.getData('id') | 0;
                    this.go('mail.view', mailid);
                }
            }
        },
        parseURLParam: function(args){
            return {mailid: args[0] | 0}
        }
    });
};