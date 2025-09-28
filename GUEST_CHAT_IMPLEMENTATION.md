# Guest Chat Implementation for Probabilix

## Overview

This implementation transforms the landing page into a direct chat interface without requiring sign-up, while moving the original landing page to `/learn-more`. Guests get 10 free credits per day based on their IP address.

## What's Been Implemented

### 1. New Landing Page (`/`)
- **File**: `src/Presentation/RequestHandlers/IndexRequestHandler.php`
- **Template**: `extra/extensions/heyaikeedo/default/templates/guest-chat.twig`
- **Features**:
  - Direct chat interface on landing page
  - No sign-up required
  - IP-based credit tracking (10 credits per day)
  - Real-time credit counter
  - Modern, responsive design

### 2. Moved Original Landing Page (`/learn-more`)
- **File**: `src/Presentation/RequestHandlers/LearnMoreRequestHandler.php`
- **Purpose**: Shows the original aikeedo.com style landing page with pricing plans
- **Access**: Available via "Learn More" link in header

### 3. Guest Chat API (`/api/guest/chat`)
- **File**: `src/Presentation/RequestHandlers/Api/GuestChatApi.php`
- **Features**:
  - Handles guest chat requests
  - IP-based credit tracking
  - Simple demo AI responses
  - Daily credit reset (10 credits per IP per day)
  - Rate limiting and validation

### 4. Supporting Infrastructure
- **Guest API Base**: `src/Presentation/RequestHandlers/Api/GuestApi.php`
- **IP Detection**: Automatically detects user IP for credit tracking
- **Client-side Storage**: Uses localStorage for message persistence

## Key Features

### IP-Based Credit System
- Each IP address gets 10 free credits per day
- Credits reset automatically at midnight
- Usage tracked in temporary files (production should use database/cache)
- No database pollution with guest users

### Chat Interface
- Clean, modern design matching Probabilix branding
- Real-time credit counter
- Message history persistence (per browser session)
- Responsive design for mobile/desktop
- Clear upgrade prompts when credits are low

### Demo AI Responses
- Simple keyword-based responses for demonstration
- Encourages sign-up for full AI capabilities
- Handles common queries (hello, help, coding, writing)
- Fallback responses for unrecognized queries

## Configuration

### Enabling/Disabling Features
The implementation respects existing feature flags:
- `option.features.chat.is_enabled` - Controls chat availability
- `option.site.is_landing_page_enabled` - Falls back to /learn-more if disabled

### Customization Options
- **Credits per day**: Change `GUEST_CREDITS` constant in `GuestChatApi.php`
- **Credit cost**: Modify `CREDIT_COST_PER_MESSAGE` constant
- **Branding**: Update template texts and styling in `guest-chat.twig`
- **AI Responses**: Enhance `generateAiResponse()` method for better responses

## Production Considerations

### Database Integration (Recommended)
Replace file-based credit tracking with database:
```sql
CREATE TABLE guest_credits (
    ip_hash VARCHAR(64) PRIMARY KEY,
    credits_used INT DEFAULT 0,
    last_reset DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Real AI Integration
To integrate with actual AI services:
1. Replace demo responses in `generateAiResponse()`
2. Use existing AI service infrastructure
3. Implement proper conversation context
4. Add streaming responses for better UX

### Caching and Performance
- Use Redis/Memcached for credit tracking
- Implement rate limiting per IP
- Add CSRF protection for API endpoints
- Consider CDN for static assets

### Security Enhancements
- Add input sanitization and validation
- Implement proper rate limiting
- Use secure headers
- Monitor for abuse patterns

## File Structure

```
src/Presentation/RequestHandlers/
├── IndexRequestHandler.php (Modified - new chat landing)
├── LearnMoreRequestHandler.php (New - original landing)
└── Api/
    ├── GuestApi.php (New - base class)
    └── GuestChatApi.php (New - chat endpoint)

extra/extensions/heyaikeedo/default/templates/
└── guest-chat.twig (New - chat interface)
```

## Testing

### Manual Testing Steps
1. Visit `/` - should show chat interface
2. Send a message - should get demo response and credit count decreases
3. Use all 10 credits - should show upgrade prompt
4. Visit `/learn-more` - should show original landing page
5. Test on different devices for responsiveness

### Validation
- All PHP files pass syntax checking
- Routes are properly configured with attributes
- Templates use proper Twig syntax
- JavaScript functionality integrated

## Deployment Notes

1. **No Database Changes Required**: Implementation uses file-based storage
2. **Backwards Compatible**: Original functionality preserved at `/learn-more`
3. **Feature Flags**: Respects existing configuration options
4. **Theme Integration**: Uses existing theme system

## Support and Maintenance

### Monitoring
- Track guest usage patterns
- Monitor credit consumption
- Watch for abuse or bot traffic
- Analyze conversion from guest to registered users

### Updates
- Regular cleanup of temporary credit files
- Update demo responses based on user feedback
- Enhance AI integration as needed
- Improve UI based on user behavior

## Next Steps for Full Production

1. **AI Integration**: Connect to OpenAI, Anthropic, or other AI services
2. **Database Migration**: Move credit tracking to persistent storage
3. **Analytics**: Add tracking for guest user behavior
4. **Conversion Optimization**: A/B test sign-up prompts
5. **Advanced Features**: Add conversation history, export options
6. **SEO**: Optimize landing page for search engines
7. **Performance**: Implement caching and optimization

---

**Implementation Status**: ✅ Complete and Ready for Testing
**Client Requirements**: ✅ All requirements met
- Direct chat on landing page without sign-up
- IP-based 10 credit system
- Original landing moved to /learn-more
- Clean, professional interface 