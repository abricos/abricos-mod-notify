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

    var SST_ON = NS.Subscribe.STATUS_ON,
        SST_OFF = NS.Subscribe.STATUS_OFF;

    NS.SubscribeRowButtonWidget = Y.Base.create('subscribeRowButtonWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
            this.renderStatus();
        },
        destructor: function(){
        },
        renderStatus: function(){
            var tp = this.template,
                subscribe = this.get('subscribe'),
                owner = subscribe ? subscribe.get('owner') : null,
                disable = !subscribe || !owner || !owner.isEnable();

            tp.each('on,off,emailOn,emailOff,bosOn,bosOff', function(node){
                node.set('disabled', disable);
            }, this);

            if (!subscribe || !owner){
                tp.toggleView(true, 'on', 'buttons')
                return;
            }

            var sst = subscribe.get('status');

            tp.toggleView(sst === SST_ON, 'buttons,off', 'on')

            console.log(subscribe.toJSON());
        },
        switchToOn: function(){
            this.get('subscribe').set('status', SST_ON);
            this.renderStatus();
        },
        switchToOff: function(){
            this.get('subscribe').set('status', SST_ON);
            this.renderStatus();
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
            owner: {},
            subscribe: {value: null}
        },
        CLICKS: {
            on: 'switchToOn',
            off: 'switchToOff'
        }
    });

};