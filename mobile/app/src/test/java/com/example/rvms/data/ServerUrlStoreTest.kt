package com.example.rvms.data

import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Test

/**
 * Turning what somebody types into a server address (2026-08).
 *
 * This gets typed on a phone keyboard, in a hurry, possibly in front of a
 * panel, from a laptop screen across the room. So it is forgiving about the
 * things people actually get wrong — a missing scheme, a trailing slash, a
 * pasted `/api/v1` — and refuses only what cannot be salvaged, because
 * refusing leaves the previous working address in place.
 */
class ServerUrlStoreTest {

    private fun norm(input: String) = ServerUrlStore.normalize(input)

    /* ---------------------------- the happy path --------------------------- */

    @Test
    fun `a full address is kept as it is`() {
        assertEquals("http://192.168.1.15:8000", norm("http://192.168.1.15:8000"))
    }

    @Test
    fun `an https address is kept as https`() {
        assertEquals("https://rvms.up.railway.app", norm("https://rvms.up.railway.app"))
    }

    /* ------------------------- what people mistype -------------------------- */

    /** A bare host becomes http, never https — anything typed without a scheme
     *  here is a local laptop, and guessing https fails like a dead server. */
    @Test
    fun `a missing scheme becomes http`() {
        assertEquals("http://192.168.1.15:8000", norm("192.168.1.15:8000"))
    }

    @Test
    fun `a trailing slash is dropped`() {
        assertEquals("http://192.168.1.15:8000", norm("http://192.168.1.15:8000/"))
    }

    @Test
    fun `a pasted api path is dropped so it cannot be applied twice`() {
        assertEquals("http://192.168.1.15:8000", norm("http://192.168.1.15:8000/api/v1"))
        assertEquals("http://192.168.1.15:8000", norm("192.168.1.15:8000/api/v1/"))
    }

    @Test
    fun `surrounding whitespace is ignored`() {
        assertEquals("http://127.0.0.1:8000", norm("  http://127.0.0.1:8000  "))
    }

    /** A stray path must not be prefixed onto every request. */
    @Test
    fun `anything after the host is discarded`() {
        assertEquals("https://rvms.example.com", norm("https://rvms.example.com/dashboard?x=1"))
    }

    /* ------------------------------ the ports ------------------------------ */

    @Test
    fun `a default port is not repeated in the stored value`() {
        assertEquals("https://rvms.example.com", norm("https://rvms.example.com:443"))
        assertEquals("http://rvms.example.com", norm("http://rvms.example.com:80"))
    }

    @Test
    fun `a non default port is kept`() {
        assertEquals("http://192.168.1.15:8000", norm("192.168.1.15:8000"))
        assertEquals("https://rvms.example.com:8443", norm("https://rvms.example.com:8443"))
    }

    /* ------------------------------- refusals ------------------------------ */

    @Test
    fun `an empty address is refused`() {
        assertNull(norm(""))
        assertNull(norm("   "))
    }

    @Test
    fun `nonsense is refused rather than stored`() {
        assertNull(norm("http://"))
        assertNull(norm("://"))
    }

    /* ------------------------------ the default ---------------------------- */

    /** A fresh install works over a USB cable with nothing typed. */
    @Test
    fun `the built-in default is the usb cable address`() {
        assertEquals("http://127.0.0.1:8000", ServerUrlStore.DEFAULT_ORIGIN)
        assertEquals(ServerUrlStore.DEFAULT_ORIGIN, norm(ServerUrlStore.DEFAULT_ORIGIN))
    }

    /** The prefix the interceptor applies must match Retrofit's base path. */
    @Test
    fun `the api prefix is the one retrofit was built with`() {
        assertEquals("/api/v1/", ServerUrlStore.API_PREFIX)
        assertEquals(
            com.example.rvms.data.remote.ApiClient.BASE_URL,
            ServerUrlStore.DEFAULT_ORIGIN + ServerUrlStore.API_PREFIX,
        )
    }
}
