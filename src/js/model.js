var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['appModel.js']}
    ]
};
Component.entryPoint = function(NS){
    var Y = Brick.YUI,
        SYS = Brick.mod.sys;


    var isOwner = function(val){
        if (!val){
            return false;
        }
        if (!Y.Lang.isFunction(val.get)){
            return false;
        }
        return true;
    };

    NS.Owner = Y.Base.create('owner', SYS.AppModel, [], {
        structureName: 'Owner',
        /*
        compare: function(val){
            if (!NS.Owner.isOwner(val)){
                return false;
            }
            return val.get('module') === this.get('module')
                && val.get('type') === this.get('type')
                && val.get('ownerid') === this.get('ownerid');
        }
        /**/
    }, {
        ATTRIBUTE: {
            validator: isOwner,
            setter: function(val){
                if (val.module && val.type && val.ownerid){
                    return this.get('appInstance').ownerCreate(val.module, val.type, val.ownerid);
                }
                return val;
            }
        },
        isOwner: isOwner
    });

    NS.OwnerList = Y.Base.create('ownerList', SYS.AppModelList, [], {
        appItem: NS.Owner
    });

    NS.Subscribe = Y.Base.create('subscribe', SYS.AppModel, [], {
        structureName: 'Subscribe'
    }, {
        STATUS_UNSET: 'unset',
        STATUS_ON: 'on',
        STATUS_OFF: 'off',
        isUnset: function(subscribe){
            if (!subscribe){
                return NS.Subscribe.STATUS_UNSET;
            }
        },
        isOn: function(subscribe){
            if (!subscribe){
                return false;
            }

        }
    });

    NS.SubscribeList = Y.Base.create('subscribeList', SYS.AppModelList, [], {
        appItem: NS.Subscribe
    });

    NS.Config = Y.Base.create('config', SYS.AppModel, [], {
        structureName: 'Config'
    });

};