var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['mail.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.MailTestWidget = Y.Base.create('MailTestWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance, options){
        },
        destructor: function(){
            this.hideResult();
        },
        sendTestMail: function(){
            var tp = this.template,
                email = tp.getValue('email');

            this.set('waiting', true);

            this.get('appInstance').mailTestSend(email, function(err, result){
                this.set('waiting', false);

                if (!err){
                    this.showResult(result.mailTestSend);
                }
            }, this);
        },
        showResult: function(mail){
            var tp = this.template;

            tp.show('resultPanel');

            this._resultMailWidget = new NS.MailViewWidget({
                srcNode: tp.append('resultMail', '<div></div>'),
                mail: mail
            });
        },
        hideResult: function(){
            var widget = this._resultMailWidget;
            if (!widget){
                return;
            }
            widget.destroy();
            this._resultMailWidget = null;

            this.template.hide('resultPanel');
        }

    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
        },
        CLICKS: {
            send: 'sendTestMail'
        },
    });
};