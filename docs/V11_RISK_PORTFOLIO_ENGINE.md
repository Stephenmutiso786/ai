# STETECH V11 Risk & Portfolio Engine

## Hard fail-closed controls
A trade is rejected if any required live broker state is missing. The engine checks trading halt, AI WAIT, confidence, entry/stop, broker connection, equity, margin, maximum open positions, daily loss, contract specifications, minimum/maximum lot, lot step, free-margin buffer and account exposure.

## Required broker snapshot fields
`equity`, `free_margin`, and `instrument.contract_size`, `min_lot`, `max_lot`, `lot_step`; optionally `margin_per_lot`.

The risk engine must run server-side immediately before every external order. UI approval never bypasses it.
