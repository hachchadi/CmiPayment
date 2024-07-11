# CmiPayment Laravel Package

## Introduction

This Laravel package integrates the CMI (Centre Monétique Interbancaire) payment gateway into your Laravel application. It provides convenient methods to initiate payments, generate hashes, and check the status of orders.

## Installation

You can install the package via Composer. Run the following command:

```bash
composer require hachchadi/cmi-payment
```
Next, publish the configuration file:

```bash
php artisan vendor:publish --provider="Hachchadi\CmiPayment\CmiPaymentServiceProvider"
```

This command will publish the cmi.php configuration file in your config directory.

## Configuration

Update your .env file with the necessary CMI configuration values:

```bash
CMI_CLIENT_ID=your_cmi_client_id
CMI_STORE_KEY=your_cmi_store_key
CMI_BASE_URI=https://testpayment.cmi.co.ma/fim/est3Dgate
CMI_OK_URL=https://your-app.com/payment/success
CMI_FAIL_URL=https://your-app.com/payment/failure
CMI_CALLBACK_URL=https://your-app.com/payment/callback
CMI_SHOP_URL=(https://your-app.com)
```

Configuration API Check Status Order

```bash
CMI_BASE_URI_API='https://testpayment.cmi.co.ma/fim/api'
CMI_API_CREDENTIALS_NAME=your_cmi_name
CMI_API_CREDENTIALS_PASSWORD=your_cmi_password
CMI_API_CREDENTIALS_CLIENT_ID=your_cmi_client_id
```

Modify config/cmi.php directly if you prefer hardcoding values or need to adjust defaults.


## Usage

### 1. Process Payment

You can initiate a payment using the CmiClient class. Here's an example of processing a payment in a controller:

```bash

use Illuminate\Http\Request;
use Hachchadi\CmiPayment\CmiClient;

class PaymentController extends Controller
{
    public function processPayment(Request $request, CmiClient $cmiClient)
    {
        try {
            $data = [
                'amount' => $request->input('amount'),
                'currency' => $request->input('currency'),
                'orderid' => $request->input('orderid'),
                'email' => $request->input('email'),
                'billToName' => $request->input('billToName'),
                // Add other required fields as needed
            ];

            // Process the payment
            $cmiClient->processPayment($data);

        } catch (\Exception $e) {
            // Handle any exceptions
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
```

### 2. Check Order Status

To check the status of an order using the CMI API, use the getCmiStatus method in your controller:

```bash

use Hachchadi\CmiPayment\CmiClient;

class OrderController extends Controller
{
    public function checkOrderStatus($orderId, CmiClient $cmiClient)
    {
        try {
            $status = $cmiClient->getCmiStatus($orderId);

            // Handle status response
            return response()->json(['status' => $status]);

        } catch (\Exception $e) {
            // Handle any exceptions
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
```

## Important Notes
#### Ensure your `.env` file is properly configured with your CMI credentials and URLs.
#### Customize the CMI configuration in `config/cmi.php` as per your integration requirements.
#### Handle exceptions and error responses appropriately in your application to provide a smooth payment experience for users.
