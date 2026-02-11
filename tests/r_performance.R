#!/usr/bin/env Rscript

args <- commandArgs(trailingOnly = TRUE)
if (length(args) < 1) {
  cat('{"error":"missing_csv_path"}\n')
  quit(status = 1)
}

csv_path <- args[1]

suppressMessages(library(MASS))
suppressMessages(library(jsonlite))

# Load data once; timing should only include computation
values <- scan(csv_path, sep = ",", quiet = TRUE)

median_time <- system.time({
  med <- median(values)
})["elapsed"] * 1000

quantile_prob <- 0.75
quantile_types <- 1:9

output <- list(
  median_ms = as.numeric(median_time),
  median = as.numeric(med)
)

for (t in quantile_types) {
  q_time <- system.time({
    q <- quantile(values, probs = quantile_prob, type = t, names = FALSE)
  })["elapsed"] * 1000

  output[[paste0("quantile_t", t, "_ms")]] <- as.numeric(q_time)
  output[[paste0("quantile_t", t, "_value")]] <- as.numeric(q)
}

huber_time <- system.time({
  h <- MASS::huber(values)
  mu <- h$mu
})["elapsed"] * 1000

trimmed_time <- system.time({
  trimmed <- mean(values, trim = 0.1)
})["elapsed"] * 1000

winsor_time <- system.time({
  ql <- quantile(values, probs = 0.1, type = 7, names = FALSE)
  qu <- quantile(values, probs = 0.9, type = 7, names = FALSE)
  winsor <- mean(pmin(pmax(values, ql), qu))
})["elapsed"] * 1000

output$huber_ms <- as.numeric(huber_time)
output$huber_mu <- as.numeric(mu)
output$trimmed_ms <- as.numeric(trimmed_time)
output$trimmed_mean <- as.numeric(trimmed)
output$winsorized_ms <- as.numeric(winsor_time)
output$winsorized_mean <- as.numeric(winsor)

cat(jsonlite::toJSON(output, auto_unbox = TRUE))
cat("\n")
