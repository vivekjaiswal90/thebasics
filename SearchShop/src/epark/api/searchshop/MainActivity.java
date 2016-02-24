package epark.api.searchshop;

import android.app.Activity;
import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.TextView;

public class MainActivity extends Activity {
	
	EditText access_tkn, pref_id, lat, lon, key, page_no;
	Button searchBtn;
	TextView resvw;
	
	@Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);
    
    	access_tkn = (EditText) findViewById(R.id.access_token);
    	pref_id = (EditText) findViewById(R.id.prefecture_id);
    	lat = (EditText) findViewById(R.id.latitude);
    	lon = (EditText) findViewById(R.id.longitude);
    	key = (EditText) findViewById(R.id.keyword);
    	page_no = (EditText) findViewById(R.id.page);
    	searchBtn = (Button) findViewById(R.id.search);
    	resvw = (TextView) findViewById(R.id.resultView);
    	
    	String access_token = access_tkn.getText().toString();
    	String prefecture_id = pref_id.getText().toString();
    	String latitude = lat.getText().toString();
    	String longitude = lon.getText().toString();
    	String keyword = key.getText().toString();
    }
    
	public void search(View v) {
		
//		List<NameValuePair> nameValuePairs = new ArrayList<NameValuePair>(6);
//		nameValuePairs.add(new BasicNameValuePair("access_token", access_tkn.getText().toString()));
//		nameValuePairs.add(new BasicNameValuePair("prefecture_id", pref_id.getText().toString()));
//		nameValuePairs.add(new BasicNameValuePair("latitude", lat.getText().toString()));
//		nameValuePairs.add(new BasicNameValuePair("longitude", lon.getText().toString()));
//		nameValuePairs.add(new BasicNameValuePair("keyword", key.getText().toString()));
//		nameValuePairs.add(new BasicNameValuePair("page", page_no.getText().toString()));
		
		GetReplyMessage message = new GetReplyMessage(resvw);
		//String msg = message.stringQuery("http:www.google.co.jp");
		message.execute(access_tkn.getText().toString(), pref_id.getText().toString(), lat.getText().toString(), lon.getText().toString(), key.getText().toString(), page_no.getText().toString()); //pass 7 parameter to this method rest 6 will be form variable
		//txtvw.setText("Success");

	}
    

}
