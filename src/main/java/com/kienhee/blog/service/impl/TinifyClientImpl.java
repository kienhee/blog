package com.kienhee.blog.service.impl;

import com.kienhee.blog.config.TinifyProperties;
import com.kienhee.blog.service.TinifyClient;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.http.HttpHeaders;
import org.springframework.http.MediaType;
import org.springframework.stereotype.Service;
import org.springframework.web.client.RestClient;

import java.nio.charset.StandardCharsets;
import java.util.Base64;
import java.util.Map;

@Slf4j
@Service
@RequiredArgsConstructor
public class TinifyClientImpl implements TinifyClient {

    private static final String SHRINK_URL = "https://api.tinify.com/shrink";

    private final TinifyProperties tinifyProperties;
    private final RestClient restClient = RestClient.create();

    @Override
    @SuppressWarnings("unchecked")
    public byte[] compress(byte[] original, String contentType) {
        if (!tinifyProperties.isEnabled()) {
            throw new IllegalStateException("Tinify API key is not configured.");
        }

        String basicAuth = "Basic " + Base64.getEncoder()
                .encodeToString(("api:" + tinifyProperties.getApiKey()).getBytes(StandardCharsets.UTF_8));

        Map<String, Object> shrinkResponse = restClient.post()
                .uri(SHRINK_URL)
                .header(HttpHeaders.AUTHORIZATION, basicAuth)
                .contentType(MediaType.parseMediaType(contentType))
                .body(original)
                .retrieve()
                .body(Map.class);

        if (shrinkResponse == null || !(shrinkResponse.get("output") instanceof Map)) {
            throw new IllegalStateException("Unexpected response from Tinify API.");
        }

        Map<String, Object> output = (Map<String, Object>) shrinkResponse.get("output");
        String outputUrl = String.valueOf(output.get("url"));

        byte[] compressed = restClient.get()
                .uri(outputUrl)
                .header(HttpHeaders.AUTHORIZATION, basicAuth)
                .retrieve()
                .body(byte[].class);

        if (compressed == null || compressed.length == 0) {
            throw new IllegalStateException("Tinify API returned an empty result.");
        }

        return compressed;
    }
}
