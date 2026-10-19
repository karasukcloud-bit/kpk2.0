package ru.kpk.attendance;

import android.os.Bundle;

import com.capacitorjs.plugins.app.AppPlugin;
import com.capacitorjs.plugins.preferences.PreferencesPlugin;
import com.getcapacitor.BridgeActivity;

import ee.forgr.capacitor_inappbrowser.InAppBrowserPlugin;
import ee.forgr.biometric.NativeBiometric;

public class MainActivity extends BridgeActivity {
    @Override
    public void onCreate(Bundle savedInstanceState) {
        registerPlugin(SessionCookiePlugin.class);
        registerPlugin(PreferencesPlugin.class);
        registerPlugin(AppPlugin.class);
        registerPlugin(InAppBrowserPlugin.class);
        registerPlugin(NativeBiometric.class);
        super.onCreate(savedInstanceState);
    }
}
