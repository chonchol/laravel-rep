# Event Ticketing Application

This is event ticketing application file. It's have complex functionality with all ecommerce feature. I have done some modules of this application. Here i have just give one controller file and modal file to show you how i work. This controller work for tickets how will be size of PDF ticketing and show ticket with complex query.

# TicketModal

TicketModal is for tickets table. Here i have created some eloquent relationship to other table here. And set some fix attribute here to calculation same thing easily in the controller.

# TicketController

TicketController extends AdminUsersBaseController. Its have `_construct` method for switch language and base_url. Its have `index` method which is to show brought tickets in the user end. And `tickets` method will create PDF form of ticket with some PDF propertise.
